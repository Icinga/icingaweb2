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
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Format;

/**
 * Drawable for creating a svg path element
 */
class Path extends Styleable implements Drawable
{
    /**
     * Syntax template for moving
     *
     * @see http://www.w3.org/TR/SVG/paths.html#PathDataMovetoCommands
     */
    const TPL_MOVE = 'M %s %s ';

    /**
     * Syntax template for bezier curve
     *
     * @see http://www.w3.org/TR/SVG/paths.html#PathDataCubicBezierCommands
     */
    const TPL_BEZIER = 'S %s %s ';

    /**
     * Syntax template for straight lines
     *
     * @see http://www.w3.org/TR/SVG/paths.html#PathDataLinetoCommands
     */
    const TPL_STRAIGHT = 'L %s %s ';

    /**
     * The default stroke width
     *
     * @var int
     */
    public $strokeWidth = 1;

    /**
     * True to treat coordinates as absolute values
     *
     * @var bool
     */
    protected $isAbsolute = false;

    /**
     * The points to draw, in the order they are drawn
     *
     * @var array
     */
    protected $points = array();

    /**
     * True to draw the path discrete, i.e. make hard steps between points
     *
     * @var bool
     */
    protected $discrete = false;

    /**
     * Create the path using the given points
     *
     * @param array $points Either a single [x, y] point or an array of x, y points
     */
    public function __construct(array $points)
    {
        $this->append($points);
    }

    /**
     * Append a single point or an array of points to this path
     *
     * @param   array $points Either a single [x, y] point or an array of x, y points
     *
     * @return  self          Fluid interface
     */
    public function append(array $points)
    {
        if (count($points) === 0) {
            return $this;
        }
        if (!is_array($points[0])) {
            $points = array($points);
        }
        $this->points = array_merge($this->points, $points);
        return $this;
    }

    /**
     * Prepend a single point or an array of points to this path
     *
     * @param   array $points Either a single [x, y] point or an array of x, y points
     *
     * @return  self          Fluid interface
     */
    public function prepend(array $points)
    {
        if (count($points) === 0) {
            return $this;
        }
        if (!is_array($points[0])) {
            $points = array($points);
        }
        $this->points = array_merge($points, $this->points);
        return $this;
    }

    /**
     * Set this path to be discrete
     *
     * @param   boolean $bool True to draw discrete or false to draw straight lines between points
     *
     * @return  self          Fluid interface
     */
    public function setDiscrete($bool)
    {
        $this->discrete = $bool;
        return $this;
    }

    /**
     * Mark this path as containing absolute coordinates
     *
     * @return self Fluid interface
     */
    public function toAbsolute()
    {
        $this->isAbsolute = true;
        return $this;
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
        $group = $doc->createElement('g');

        $pathDescription = '';
        $tpl = self::TPL_MOVE;
        $lastPoint = null;
        foreach ($this->points as $point) {
            if (!$this->isAbsolute) {
                $point = $ctx->toAbsolute($point[0], $point[1]);
            }
            $point[0] = Format::formatSVGNumber($point[0]);
            $point[1] = Format::formatSVGNumber($point[1]);
            if ($lastPoint && $this->discrete) {
                $pathDescription .= sprintf($tpl, $point[0], $lastPoint[1]);
            }
            $pathDescription .= vsprintf($tpl, $point);
            $lastPoint = $point;
            $tpl = self::TPL_STRAIGHT;
        }

        $path = $doc->createElement('path');
        if ($this->id) {
            $path->setAttribute('id', $this->id);
        }
        $path->setAttribute('d', $pathDescription);
        $path->setAttribute('style', $this->getStyle());
        $this->applyAttributes($path);
        $group->appendChild($path);
        return $group;
    }
}
