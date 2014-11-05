<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}


namespace Icinga\Chart\Primitive;

use DOMElement;
use Icinga\Chart\Render\LayoutBox;
use Icinga\Chart\Render\RenderContext;

/**
 * Canvas SVG component that encapsulates grouping and padding and allows rendering
 * multiple elements in a group
 *
 */
class Canvas implements Drawable
{
    /**
     * The name of the canvas, will be used as the id
     *
     * @var string
     */
    private $name;

    /**
     * An array of child elements of this Canvas
     *
     * @var array
     */
    private $children = array();

    /**
     * When true, this canvas is encapsulated in a clipPath tag and not drawn
     *
     * @var bool
     */
    private $isClipPath = false;

    /**
     * The LayoutBox of this Canvas
     *
     * @var LayoutBox
     */
    private $rect;

    /**
     * Create this canvas
     *
     * @param String    $name The name of this canvas
     * @param LayoutBox $rect The layout and size of this canvas
     */
    public function __construct($name, LayoutBox $rect)
    {
        $this->rect = $rect;
        $this->name = $name;
    }

    /**
     * Convert this canvas to a clipPath element
     */
    public function toClipPath()
    {
        $this->isClipPath = true;
    }

    /**
     * Return the layout of this canvas
     *
     * @return LayoutBox
     */
    public function getLayout()
    {
        return $this->rect;
    }

    /**
     * Add an element to this canvas
     *
     * @param Drawable $child
     */
    public function addElement(Drawable $child)
    {
        $this->children[] = $child;
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
        if ($this->isClipPath) {
            $outer = $doc->createElement('defs');
            $innerContainer = $element = $doc->createElement('clipPath');
            $outer->appendChild($element);
        } else {
            $outer = $element = $doc->createElement('g');
            $innerContainer = $doc->createElement('g');
            $innerContainer->setAttribute('x', 0);
            $innerContainer->setAttribute('y', 0);
            $innerContainer->setAttribute('id', $this->name . '_inner');
            $innerContainer->setAttribute('transform', $this->rect->getInnerTransform($ctx));
            $element->appendChild($innerContainer);
        }

        $element->setAttribute('id', $this->name);
        foreach ($this->children as $child) {
            $innerContainer->appendChild($child->toSvg($ctx));
        }

        return $outer;
    }
}
