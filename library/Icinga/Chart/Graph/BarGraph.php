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
use Icinga\Chart\Primitive\Rect;
use Icinga\Chart\Primitive\Styleable;
use Icinga\Chart\Render\RenderContext;

class BarGraph extends Styleable implements Drawable
{
    private $dataSet;
    public $fill = 'green';
    public function __construct(array $dataSet)
    {
        $this->dataSet = $dataSet;
    }

    public function toSvg(RenderContext $ctx)
    {
        $doc = $ctx->getDocument();
        $group = $doc->createElement('g');
        foreach($this->dataSet as $point)
        {
            $rect = new Rect($point[0]-2, $point[1], 4, 100- $point[1]);
            $rect->setFill($this->fill);
            $rect->setStrokeWidth($this->strokeWidth);
            $rect->setStrokeColor($this->strokeColor);

            $rect->setAdditionalStyle('clip-path: url(#clip);');

            $group->appendChild($rect->toSvg($ctx));
        }
        return $group;
    }

    public function setStyleFromConfig($cfg)
    {
        foreach ($cfg as $elem => $value) {
            if ($elem === 'color') {
                $this->setStrokeColor($value);
            } else if ($elem === 'width') {
                $this->setStrokeWidth($value);
            } else if ($elem === 'showPoints') {
                $this->setShowDataPoints($value);
            } else if ($elem == 'fill') {
                $this->setFill($value);
            }
        }
    }
}