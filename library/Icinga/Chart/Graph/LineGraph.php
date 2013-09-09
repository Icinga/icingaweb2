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


namespace Icinga\Chart\Graph;

use Icinga\Chart\Primitive\Drawable;
use Icinga\Chart\Primitive\Path;
use Icinga\Chart\Primitive\Circle;
use Icinga\Chart\Primitive\Styleable;
use Icinga\Chart\Render\RenderContext;

class LineGraph extends Styleable implements Drawable
{

    private $showDataPoints = false;

    public function __construct(array $dataset)
    {
        usort($dataset, array($this, 'sortByX'));
        $this->dataset = $dataset;
    }

    public function setShowDataPoints($bool)
    {
        $this->showDataPoints = true;
    }

    private function sortByX(array $v1, array $v2) {
        if($v1[0] === $v2[0]) {
            return 0;
        }
        return ($v1[0] < $v2[0]) ? -1 : 1;
    }

    public function toSvg(RenderContext $ctx)
    {
        $path = new Path($this->dataset);
        $path->setStrokeColor($this->strokeColor);
        $path->setStrokeWidth($this->strokeWidth);
        $group = $path->toSvg($ctx);
        if ($this->showDataPoints === true) {
            foreach ($this->dataset as $point) {
                $dot = new Circle($point[0], $point[1], 25);
                $dot->setFill($this->strokeColor);
                $group->appendChild($dot->toSvg($ctx));
            }
        }
        return $group;
    }

    public function setStyleFromConfig($cfg)
    {
        foreach ($cfg as $elem=>$value) {
            if ($elem === 'color') {
                $this->setStrokeColor($value);
            } else if ($elem === 'width') {
                $this->setStrokeWidth($value);
            } else if ($elem === 'showPoints') {
                $this->setShowDataPoints($value);
            }
        }
    }

}