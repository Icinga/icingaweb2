<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Render;

use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Primitive\Drawable;
use DOMElement;

/**
 * Class Rotator
 * @package Icinga\Chart\Render
 */
class Rotator implements Drawable
{
    /**
     * The drawable element to rotate
     *
     * @var Drawable
     */
    private $element;

    /**
     * @var int
     */
    private $degrees;

    /**
     * Wrap an element into a new instance of Rotator
     *
     * @param Drawable      $element    The element to rotate
     * @param int           $degrees    The amount of degrees
     */
    public function __construct(Drawable $element, $degrees)
    {
        $this->element = $element;
        $this->degrees = $degrees;
    }

    /**
     * Rotate the given element.
     *
     * @param RenderContext $ctx        The rendering context
     * @param DOMElement    $el         The element to rotate
     * @param               $degrees    The amount of degrees
     *
     * @return DOMElement   The rotated DOMElement
     */
    private function rotate(RenderContext $ctx, DOMElement $el, $degrees)
    {
        // Create a box containing the rotated element relative to the original element position
        $container = $ctx->getDocument()->createElement('g');
        $x = $el->getAttribute('x');
        $y = $el->getAttribute('y');
        $container->setAttribute('transform', 'translate(' . $x . ',' . $y . ')');
        $el->removeAttribute('x');
        $el->removeAttribute('y');

        // Put the element into a rotated group
        //$rotate = $ctx->getDocument()->createElement('g');
        $el->setAttribute('transform', 'rotate(' . $degrees . ')');
        //$rotate->appendChild($el);

        $container->appendChild($el);
        return $container;
    }

    /**
     * Create the SVG representation from this Drawable
     *
     * @param   RenderContext $ctx The context to use for rendering
     *
     * @return  DOMElement         The SVG Element
     */
    public function toSvg(RenderContext $ctx)
    {
        $el = $this->element->toSvg($ctx);
        return $this->rotate($ctx, $el, $this->degrees);
    }
}
