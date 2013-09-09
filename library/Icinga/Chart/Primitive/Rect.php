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

use Icinga\Chart\Render\RenderContext;

class Rect extends Styleable implements Drawable
{

    private $x;
    private $y;
    private $width;
    private $height;


    public function __construct($x, $y, $width, $height)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }


    public function toSvg(RenderContext $ctx)
    {
        $doc = $ctx->getDocument();
        $rect = $doc->createElement('rect');

        list($x, $y) = $ctx->toAbsolute($this->x, $this->y);
        list($width, $height) = $ctx->toAbsolute($this->width, $this->height);
        $rect->setAttribute('x', $x);
        $rect->setAttribute('y', $y);
        $rect->setAttribute('width', $width);
        $rect->setAttribute('height', $height);
        $rect->setAttribute('style', $this->getStyle());
        return $rect;
    }
}