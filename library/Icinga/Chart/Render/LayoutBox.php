<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Render;

use Icinga\Chart\Format;

/**
 * Layout class encapsulating size, padding and margin information
 */
class LayoutBox
{
    /**
     * Padding index for top padding
     */
    const PADDING_TOP = 0;

    /**
     * Padding index for right padding
     */
    const PADDING_RIGHT = 1;

    /**
     * Padding index for bottom padding
     */
    const PADDING_BOTTOM = 2;

    /**
     * Padding index for left padding
     */
    const PADDING_LEFT = 3;

    /**
     * The height of this layout element
     *
     * @var int
     */
    private $height;

    /**
     * The width of this layout element
     *
     * @var int
     */
    private $width;

    /**
     * The x position of this layout
     *
     * @var int
     */
    private $x;

    /**
     * The y position of this layout
     *
     * @var int
     */
    private $y;

    /**
     * The padding of this layout
     *
     * @var array
     */
    private $padding = array(0, 0, 0, 0);

    /**
     * Create this layout box
     *
     * Note that x, y, width and height are relative: x with 0 means leftmost, x with 100 means rightmost
     *
     * @param int $x        The relative x coordinate
     * @param int $y        The relative y coordinate
     * @param int $width    The optional, relative width
     * @param int $height   The optional, relative height
     */
    public function __construct($x, $y, $width = null, $height = null)
    {
        $this->height = $height ? $height : 100;
        $this->width =  $width ? $width : 100;
        $this->x =  $x;
        $this->y =  $y;
    }

    /**
     * Set a padding to all four sides uniformly
     *
     * @param int $padding The padding to set for all four sides
     */
    public function setUniformPadding($padding)
    {
        $this->padding = array($padding, $padding, $padding, $padding);
    }

    /**
     * Set the padding for this LayoutBox
     *
     * @param int $top      The top side padding
     * @param int $right    The right side padding
     * @param int $bottom   The bottom side padding
     * @param int $left     The left side padding
     */
    public function setPadding($top, $right, $bottom, $left)
    {
        $this->padding = array($top, $right, $bottom, $left);
    }

    /**
     * Return a string containing the SVG transform attribute values for the padding
     *
     * @param   RenderContext $ctx  The context to determine the translation coordinates
     *
     * @return  string              The transformation string
     */
    public function getInnerTransform(RenderContext $ctx)
    {
        list($translateX, $translateY) = $ctx->toAbsolute(
            $this->padding[self::PADDING_LEFT] + $this->getX(),
            $this->padding[self::PADDING_TOP] + $this->getY()
        );
        list($scaleX, $scaleY) = $ctx->paddingToScaleFactor($this->padding);

        $scaleX *= $this->getWidth()/100;
        $scaleY *= $this->getHeight()/100;
        return sprintf(
            'translate(%s, %s) scale(%s, %s)',
            Format::formatSVGNumber($translateX),
            Format::formatSVGNumber($translateY),
            Format::formatSVGNumber($scaleX),
            Format::formatSVGNumber($scaleY)
        );
    }

    /**
     * String representation for this Layout, for debug purposes
     *
     * @return string A string containing the bounds of this LayoutBox
     */
    public function __toString()
    {
        return sprintf(
            'Rectangle: x: %s  y: %s, height: %s, width: %s',
            $this->x,
            $this->y,
            $this->height,
            $this->width
        );
    }

    /**
     * Return a four element array with the padding
     *
     * @return array The padding of this LayoutBox
     */
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * Return the height of this LayoutBox
     *
     * @return int The height of this box
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Return the width of this LayoutBox
     *
     * @return int The width of this box
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Return the x position of this LayoutBox
     *
     * @return int The x position of this box
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Return the y position of this LayoutBox
     *
     * @return int  The y position of this box
     */
    public function getY()
    {
        return $this->y;
    }
}
