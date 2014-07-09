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

use \DOMElement;
use \Icinga\Chart\Render\RenderContext;

/**
 * Drawable for svg circles
 */
class Circle extends Styleable implements Drawable
{
    /**
     * The circles x position
     *
     * @var int
     */
    private $x;

    /**
     * The circles y position
     *
     * @var int
     */
    private $y;

    /**
     * The circles radius
     *
     * @var int
     */
    private $radius;

    /**
     * Construct the circle
     *
     * @param int $x        The x position of the circle
     * @param int $y        The y position of the circle
     * @param int $radius   The radius of the circle
     */
    public function __construct($x, $y, $radius)
    {
        $this->x = $x;
        $this->y = $y;
        $this->radius = $radius;
    }

    /**
     * Create the SVG representation from this Drawable
     *
     * @param   RenderContext $ctx The context to use for rendering
     * @return  DOMElement         The SVG Element
     */
    public function toSvg(RenderContext $ctx)
    {
        $coords = $ctx->toAbsolute($this->x, $this->y);
        $circle = $ctx->getDocument()->createElement('circle');
        $circle->setAttribute('cx', Format::formatSVGNumber($coords[0]));
        $circle->setAttribute('cy', Format::formatSVGNumber($coords[1]));
        $circle->setAttribute('r', 5);
        $circle->setAttribute('style', $this->getStyle());
        $this->applyAttributes($circle);
        return $circle;
    }
}
