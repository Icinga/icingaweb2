<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Primitive;

use DomElement;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Format;

/**
 * Drawable representing the SVG rect element
 */
class Rect extends Animatable implements Drawable
{
    /**
     * The x position
     *
     * @var int
     */
    private $x;

    /**
     * The y position
     *
     * @var int
     */
    private $y;

    /**
     * The width of this rect
     *
     * @var int
     */
    private $width;

    /**
     * The height of this rect
     *
     * @var int
     */
    private $height;

    /**
     * Whether to keep the ratio
     *
     * @var bool
     */
    private $keepRatio = false;

    /**
     * Create this rect
     *
     * @param int $x        The x position of the rect
     * @param int $y        The y position of the rectangle
     * @param int $width    The width of the rectangle
     * @param int $height   The height of the rectangle
     */
    public function __construct($x, $y, $width, $height)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Call to let the rectangle keep the ratio
     */
    public function keepRatio()
    {
        $this->keepRatio = true;
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
        $rect = $doc->createElement('rect');

        list($x, $y) = $ctx->toAbsolute($this->x, $this->y);
        if ($this->keepRatio) {
            $ctx->keepRatio();
        }
        list($width, $height) = $ctx->toAbsolute($this->width, $this->height);
        if ($this->keepRatio) {
            $ctx->ignoreRatio();
        }
        $rect->setAttribute('x', Format::formatSVGNumber($x));
        $rect->setAttribute('y', Format::formatSVGNumber($y));
        $rect->setAttribute('width', Format::formatSVGNumber($width));
        $rect->setAttribute('height', Format::formatSVGNumber($height));
        $rect->setAttribute('style', $this->getStyle());

        $this->applyAttributes($rect);
        $this->appendAnimation($rect, $ctx);

        return $rect;
    }
}
