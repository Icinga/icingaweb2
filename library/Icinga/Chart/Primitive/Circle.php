<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */


namespace Icinga\Chart\Primitive;

use DOMElement;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Format;

/**
 * Drawable for svg circles
 */
class Circle extends Styleable implements Drawable
{
    /**
     * The circles x position
     *
     * @var int
     */
    private $x;

    /**
     * The circles y position
     *
     * @var int
     */
    private $y;

    /**
     * The circles radius
     *
     * @var int
     */
    private $radius;

    /**
     * Construct the circle
     *
     * @param int $x        The x position of the circle
     * @param int $y        The y position of the circle
     * @param int $radius   The radius of the circle
     */
    public function __construct($x, $y, $radius)
    {
        $this->x = $x;
        $this->y = $y;
        $this->radius = $radius;
    }

    /**
     * Create the SVG representation from this Drawable
     *
     * @param   RenderContext $ctx The context to use for rendering
     * @return  DOMElement         The SVG Element
     */
    public function toSvg(RenderContext $ctx)
    {
        $coords = $ctx->toAbsolute($this->x, $this->y);
        $circle = $ctx->getDocument()->createElement('circle');
        $circle->setAttribute('cx', Format::formatSVGNumber($coords[0]));
        $circle->setAttribute('cy', Format::formatSVGNumber($coords[1]));
        $circle->setAttribute('r', $this->radius);
        $circle->setAttribute('style', $this->getStyle());
        $this->applyAttributes($circle);
        return $circle;
    }
}
