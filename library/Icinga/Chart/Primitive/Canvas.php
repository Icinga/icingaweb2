<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}


namespace Icinga\Chart\Primitive;

use Icinga\Chart\Render\LayoutBox;
use Icinga\Chart\Render\RenderContext;

use Icinga\Util\Dimension;

class Canvas implements Drawable {

    private $name;
    private $children = array();
    private $isClipPath = false;
    private $keepRatio = false;

    public function __construct($name, LayoutBox $rect) {
        $this->rect = $rect;
        $this->name = $name;
    }

    public function toClipPath()
    {
        $this->isClipPath = true;
    }

    public function toSvg(RenderContext $ctx) {
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
        $element->setAttribute('transform', $this->rect->getOuterTranslate($ctx));

        foreach($this->children as $child) {
            $innerContainer->appendChild($child->toSvg($ctx));
        }

        return $outer;
    }


    public function getLayout() {
        return $this->rect;
    }

    public function addElement(Drawable $child) {
        $this->children[] = $child;
     }
}