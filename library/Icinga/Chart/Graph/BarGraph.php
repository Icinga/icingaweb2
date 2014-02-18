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

namespace Icinga\Chart\Graph;

use \DOMElement;
use \Icinga\Chart\Primitive\Animation;
use \Icinga\Chart\Primitive\Drawable;
use \Icinga\Chart\Primitive\Rect;
use \Icinga\Chart\Primitive\Styleable;
use \Icinga\Chart\Render\RenderContext;

/**
 * Bar graph implementation
 */
class BarGraph extends Styleable implements Drawable
{
    /**
     * The width of the bars.
     *
     * @var int
     */
    private $barWidth = 4;

    /**
     * The dataset to use for this bar graph
     *
     * @var array
     */
    private $dataSet;

    /**
     * Create a new BarGraph with the given dataset
     *
     * @param array $dataSet    An array of datapoints
     */
    public function __construct(array $dataSet)
    {
        $this->dataSet = $dataSet;
    }

    /**
     * Apply configuration styles from the $cfg
     *
     * @param array $cfg        The configuration as given in the drawBars call
     */
    public function setStyleFromConfig(array $cfg)
    {
        foreach ($cfg as $elem => $value) {
            if ($elem === 'color') {
                $this->setFill($value);
            } elseif ($elem === 'width') {
                $this->setStrokeWidth($value);
            }
        }
    }

    /**
     * Render this BarChart
     *
     * @param   RenderContext   $ctx    The rendering context to use for drawing
     *
     * @return  DOMElement      $dom    Element
     */
    public function toSvg(RenderContext $ctx)
    {
        $doc = $ctx->getDocument();
        $group = $doc->createElement('g');
        $idx = 0;
        foreach ($this->dataSet as $point) {
            $rect = new Rect($point[0]-1, $point[1], $this->barWidth, 100- $point[1]);
            $rect->setFill($this->fill);
            $rect->setStrokeWidth($this->strokeWidth);
            $rect->setStrokeColor('black');
            $rect->setAttribute('data-icinga-graph-index', $idx++);
            $rect->setAttribute('data-icinga-graph-type', 'bar');
            $rect->setAdditionalStyle('clip-path: url(#clip);');
            /*$rect->setAnimation(
                new Animation(
                    'y',
                    $ctx->yToAbsolute(100),
                    $ctx->yToAbsolute($point[1]),
                    rand(1, 1.5)/2
                )
            );*/
            $group->appendChild($rect->toSvg($ctx));
        }
        return $group;
    }
}
