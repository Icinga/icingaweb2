<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
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
