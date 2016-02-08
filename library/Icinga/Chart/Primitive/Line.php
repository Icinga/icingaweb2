<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Primitive;

use DOMElement;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Format;

/**
 * Drawable for the svg line element
 */
class Line extends Styleable implements Drawable
{

    /**
     * The default stroke width
     *
     * @var int
     */
    public $strokeWidth = 1;

    /**
     * The line's start x coordinate
     *
     * @var int
     */
    private $xStart = 0;

    /**
     * The line's end x coordinate
     *
     * @var int
     */
    private $xEnd = 0;

    /**
     * The line's start y coordinate
     *
     * @var int
     */
    private $yStart = 0;

    /**
     * The line's end y coordinate
     *
     * @var int
     */
    private $yEnd = 0;

    /**
     * Create a line object starting at the first coordinate and ending at the second one
     *
     * @param int $x1   The line's start x coordinate
     * @param int $y1   The line's start y coordinate
     * @param int $x2   The line's end x coordinate
     * @param int $y2   The line's end y coordinate
     */
    public function __construct($x1, $y1, $x2, $y2)
    {
        $this->xStart = $x1;
        $this->xEnd = $x2;
        $this->yStart = $y1;
        $this->yEnd = $y2;
    }

    /**
     * Create the SVG representation from this Drawable
     *
     * @param   RenderContext $ctx The context to use for rendering
     * @return  DOMElement         The SVG Element
     */
    public function toSvg(RenderContext $ctx)
    {
        $doc = $ctx->getDocument();
        list($x1, $y1) = $ctx->toAbsolute($this->xStart, $this->yStart);
        list($x2, $y2) = $ctx->toAbsolute($this->xEnd, $this->yEnd);
        $line = $doc->createElement('line');
        $line->setAttribute('x1', Format::formatSVGNumber($x1));
        $line->setAttribute('x2', Format::formatSVGNumber($x2));
        $line->setAttribute('y1', Format::formatSVGNumber($y1));
        $line->setAttribute('y2', Format::formatSVGNumber($y2));
        $line->setAttribute('style', $this->getStyle());
        $this->applyAttributes($line);
        return $line;
    }
}
