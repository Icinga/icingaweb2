<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */


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
     * The aria role used to describe this canvas' purpose in the accessibility tree
     *
     * @var string
     */
    private $ariaRole;

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

        if (isset($this->ariaRole)) {
            $outer->setAttribute('role', $this->ariaRole);
        }
        return $outer;
    }

    /**
     * Set the aria role used to determine the meaning of this canvas in the accessibility tree
     *
     * The role 'presentation' will indicate that the purpose of this canvas is entirely decorative, while the role
     * 'img' will indicate that the canvas contains an image, with a possible title or a description. For other
     * possible roles, see http://www.w3.org/TR/wai-aria/roles
     *
     * @param $role string  The aria role to set
     */
    public function setAriaRole($role)
    {
        $this->ariaRole = $role;
    }
}
