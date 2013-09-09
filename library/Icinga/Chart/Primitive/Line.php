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

class Line extends Styleable implements Drawable {
    public $strokeWidth = 1;

    private $xStart = 0;
    private $xEnd = 0;
    private $yStart = 0;
    private $yEnd = 0;


    public function __construct($x1, $y1, $x2, $y2)
    {
        $this->xStart = $x1;
        $this->xEnd = $x2;
        $this->yStart = $y1;
        $this->yEnd = $y2;
    }

    public function toSvg(RenderContext $ctx)
    {
        $doc = $ctx->getDocument();
        list($x1, $y1) = $ctx->toAbsolute($this->xStart, $this->yStart);
        list($x2, $y2) = $ctx->toAbsolute($this->xEnd, $this->yEnd);
        $line = $doc->createElement('line');
        $line->setAttribute('x1', $x1);
        $line->setAttribute('x2', $x2);
        $line->setAttribute('y1', $y1);
        $line->setAttribute('y2', $y2);
        $line->setAttribute('style', $this->getStyle());
        return $line;
    }
}