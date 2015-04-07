<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Primitive;

use DOMElement;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Format;

/**
 * Component for drawing a pie slice
 */
class PieSlice extends Animatable implements Drawable
{
    /**
     * The radius of this pieslice relative to the canvas
     *
     * @var int
     */
    private $radius = 50;

    /**
     * The start radian of the pie slice
     *
     * @var float
     */
    private $startRadian = 0;

    /**
     * The end radian of the pie slice
     *
     * @var float
     */
    private $endRadian = 0;

    /**
     * The x position of the pie slice's center
     *
     * @var int
     */
    private $x;

    /**
     * The y position of the pie slice's center
     *
     * @var int
     */
    private $y;

    /**
     * The caption of the pie slice, empty string means no caption
     *
     * @var string
     */
    private $caption = "";

    /**
     * The offset of the caption, shifting the indicator from the center of the pie slice
     *
     * This is required for nested pie slices.
     *
     * @var int
     */
    private $captionOffset = 0;

    /**
     * The minimum radius the label must respect
     *
     * @var int
     */
    private $outerCaptionBound = 0;

    /**
     * An optional group element to add labels to when rendering
     *
     * @var DOMElement
     */
    private $labelGroup;

    /**
     * Create a pie slice
     *
     * @param int $radius       The radius of the slice
     * @param int $percent      The percentage the slice represents
     * @param int $percentStart The percentage where this slice starts
     */
    public function __construct($radius, $percent, $percentStart = 0)
    {
        $this->x = $this->y = $this->radius = $radius;

        $this->startRadian  = M_PI * $percentStart/50;
        $this->endRadian    = M_PI * ($percent + $percentStart)/50;
    }

    /**
     * Create the path for the pie slice
     *
     * @param   int $x      The x position of the pie slice
     * @param   int $y      The y position of the pie slice
     * @param   int $r      The absolute radius of the pie slice
     *
     * @return  string      A SVG path string
     */
    private function getPieSlicePath($x, $y, $r)
    {
        // The coordinate system is mirrored on the Y axis, so we have to flip cos and sin
        $xStart = $x + ($r * sin($this->startRadian));
        $yStart = $y - ($r * cos($this->startRadian));

        if ($this->endRadian - $this->startRadian == 2*M_PI) {
            // To draw a full circle, adjust arc endpoint by a small (unvisible) value
            $this->endRadian -= 0.001;
            $pathString = 'M ' . Format::formatSVGNumber($xStart) . ' ' . Format::formatSVGNumber($yStart);
        } else {
            // Start at the center of the pieslice
            $pathString =  'M ' . $x . ' ' . $y;
            // Draw a straight line to the upper part of the arc
            $pathString .= ' L ' . Format::formatSVGNumber($xStart) . ' ' . Format::formatSVGNumber($yStart);
        }

        // Instead of directly connecting the upper part of the arc (leaving a triangle), draw a bow with the radius
        $pathString .= ' A ' . Format::formatSVGNumber($r) . ' ' . Format::formatSVGNumber($r);
        // These are the flags for the bow, see the SVG path documentation for details
        // http://www.w3.org/TR/SVG/paths.html#PathDataEllipticalArcCommands
        $pathString .= ' 0 ' . (($this->endRadian - $this->startRadian > M_PI) ?  '1'  : '0 ') . ' 1';

        // xEnd and yEnd are the lower point of the arc
        $xEnd = $x + ($r * sin($this->endRadian));
        $yEnd = $y - ($r * cos($this->endRadian));
        $pathString .= ' ' . Format::formatSVGNumber($xEnd) . ' ' . Format::formatSVGNumber($yEnd);

        return $pathString;
    }

    /**
     * Draw the label handler and the text for this pie slice
     *
     * @param   RenderContext $ctx  The rendering context to use for coordinate translation
     * @param   int           $r    The radius of the pie in absolute coordinates
     *
     * @return  DOMElement          The group DOMElement containing the handle and label
     */
    private function drawDescriptionLabel(RenderContext $ctx, $r)
    {
        $group = $ctx->getDocument()->createElement('g');
        $rOuter = ($ctx->xToAbsolute($this->outerCaptionBound) + $ctx->yToAbsolute($this->outerCaptionBound)) / 2;
        $addOffset = $rOuter - $r ;
        if ($addOffset < 0) {
            $addOffset = 0;
        }
        list($x, $y) = $ctx->toAbsolute($this->x, $this->y);
        $midRadius = $this->startRadian + ($this->endRadian - $this->startRadian) / 2;
        list($offsetX, $offsetY) = $ctx->toAbsolute($this->captionOffset, $this->captionOffset);

        $midX = $x + intval(($offsetX + $r)/2 * sin($midRadius));
        $midY = $y - intval(($offsetY + $r)/2 * cos($midRadius));

        // Draw the handle
        $path = new Path(array($midX, $midY));

        $midX += ($addOffset + $r/3) * ($midRadius > M_PI ? -1 : 1);
        $path->append(array($midX, $midY))->toAbsolute();

        $midX += intval($r/2 * sin(M_PI/9)) * ($midRadius > M_PI ? -1 : 1);
        $midY -= intval($r/2 * cos(M_PI/3)) * ($midRadius < M_PI*1.4 && $midRadius > M_PI/3 ? -1 : 1);

        if ($ctx->ytoRelative($midY) > 100) {
            $midY = $ctx->yToAbsolute(100);
        } elseif ($ctx->ytoRelative($midY) < 0) {
            $midY = $ctx->yToAbsolute($ctx->ytoRelative(100+$midY));
        }

        $path->append(array($midX , $midY));
        $rel = $ctx->toRelative($midX, $midY);

        // Draw the text box
        $text = new Text($rel[0]+1.5, $rel[1], $this->caption);
        $text->setFontSize('5em');
        $text->setAlignment(($midRadius > M_PI ? Text::ALIGN_END : Text::ALIGN_START));

        $group->appendChild($path->toSvg($ctx));
        $group->appendChild($text->toSvg($ctx));

        return $group;
    }

    /**
     * Set the x position of the pie slice
     *
     * @param   int $x The new x position
     *
     * @return  $this   Fluid interface
     */
    public function setX($x)
    {
        $this->x = $x;
        return $this;
    }

    /**
     * Set the y position of the pie slice
     *
     * @param   int $y  The new y position
     *
     * @return  $this    Fluid interface
     */
    public function setY($y)
    {
        $this->y = $y;
        return $this;
    }

    /**
     * Set a root element to be used for drawing labels
     *
     * @param   DOMElement $group   The label group
     *
     * @return  $this                Fluid interface
     */
    public function setLabelGroup(DOMElement $group)
    {
        $this->labelGroup = $group;
        return $this;
    }

    /**
     * Set the caption for this label
     *
     * @param   string $caption The caption for this element
     *
     * @return  $this            Fluid interface
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * Set the internal offset of the caption handle
     *
     * @param   int $offset     The offset for the caption handle
     *
     * @return  $this            Fluid interface
     */
    public function setCaptionOffset($offset)
    {
        $this->captionOffset = $offset;
        return $this;
    }

    /**
     * Set the minimum radius to be used for drawing labels
     *
     * @param   int $bound  The offset for the caption text
     *
     * @return  $this        Fluid interface
     */
    public function setOuterCaptionBound($bound)
    {
        $this->outerCaptionBound = $bound;
        return $this;
    }

    /**
     * Create the SVG representation from this Drawable
     *
     * @param   RenderContext $ctx  The context to use for rendering
     *
     * @return  DOMElement          The SVG Element
     */
    public function toSvg(RenderContext $ctx)
    {
        $doc = $ctx->getDocument();
        $group = $doc->createElement('g');
        $r = ($ctx->xToAbsolute($this->radius) + $ctx->yToAbsolute($this->radius)) / 2;
        list($x, $y) = $ctx->toAbsolute($this->x, $this->y);

        $slicePath = $doc->createElement('path');

        $slicePath->setAttribute('d', $this->getPieSlicePath($x, $y, $r));
        $slicePath->setAttribute('style', $this->getStyle());
        $slicePath->setAttribute('data-icinga-graph-type', 'pieslice');

        $this->applyAttributes($slicePath);
        $group->appendChild($slicePath);
        if ($this->caption != "") {
            $lblGroup = ($this->labelGroup ? $this->labelGroup : $group);
            $lblGroup->appendChild($this->drawDescriptionLabel($ctx, $r));
        }
        return $group;
    }
}
