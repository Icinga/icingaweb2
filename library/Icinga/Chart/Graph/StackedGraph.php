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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Chart\Graph;

use \DOMElement;
use \Icinga\Chart\Primitive\Drawable;
use \Icinga\Chart\Render\RenderContext;

/**
 * Graph implementation that stacks several graphs and displays them in a cumulative way
 */
class StackedGraph implements Drawable
{
    /**
     * All graphs displayed in this stackedgraph
     *
     * @var array
     */
    private $stack = array();

    /**
     * An associative array containing x points as the key and an array of y values as the value
     *
     * @var array
     */
    private $points = array();

    /**
     * Add a graph to this stack and aggregate the values on the fly
     *
     * This modifies the dataset as a side effect
     *
     * @param array $subGraph
     */
    public function addGraph(array &$subGraph)
    {
        foreach ($subGraph['data'] as &$point) {
            $x = $point[0];
            if (!isset($this->points[$x])) {
                $this->points[$x] = 0;
            }
            $this->points[$x] += $point[1];
            $point[1] = $this->points[$x];
        }
    }

    /**
     * Add a graph to the stack
     *
     * @param $graph
     */
    public function addToStack($graph)
    {
        $this->stack[] = $graph;
    }

    /**
     * Empty the stack
     *
     * @return bool
     */
    public function stackEmpty()
    {
        return empty($this->stack);
    }

    /**
     * Render this stack in the correct order
     *
     * @param   RenderContext $ctx  The context to use for rendering
     *
     * @return  DOMElement          The SVG representation of this graph
     */
    public function toSvg(RenderContext $ctx)
    {
        $group = $ctx->getDocument()->createElement('g');
        $renderOrder = array_reverse($this->stack);
        foreach ($renderOrder as $stackElem) {
            $group->appendChild($stackElem->toSvg($ctx));
        }
        return $group;
    }
}
