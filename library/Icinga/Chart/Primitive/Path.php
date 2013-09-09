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

class Path extends Styleable implements Drawable {
    public $strokeWidth = 1;

    protected $points = array();
    protected $smooth = false;

    const TPL_MOVE = "M %s %s ";
    const TPL_BEZIER = "S %s %s ";
    const TPL_STRAIGHT = "L %s %s ";

    public function __construct(array $points)
    {
        $this->append($points);
    }

    public function append(array $points)
    {
        $this->points += $points;
        return $this;
    }

    public function setSmooth($bool)
    {
        $this->smooth = $bool;
    }


    public function toSvg(RenderContext $ctx) {
        $doc = $ctx->getDocument();
        $group = $doc->createElement('g');

        $pathDescription = '';
        $tpl = self::TPL_MOVE;

        foreach ($this->points as $point) {
            $coords = $ctx->toAbsolute($point[0], $point[1]);
            $pathDescription .= vsprintf($tpl, $coords);

            $tpl = self::TPL_STRAIGHT;
        }
        $path = $doc->createElement('path');
        $path->setAttribute('d', $pathDescription);
        $path->setAttribute('style', $this->getStyle());
        $group->appendChild($path);
        return $group;
    }

}