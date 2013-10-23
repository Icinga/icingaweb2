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
 * Drawable for the svg line element
 */
class Line extends Styleable implements Drawable
{

    /**
     * The default stroke width
     *
     * @var int
     */
    public $strokeWidth = 1;

    /**
     * The line's start x coordinate
     *
     * @var int
     */
    private $xStart = 0;

    /**
     * The line's end x coordinate
     *
     * @var int
     */
    private $xEnd = 0;

    /**
     * The line's start y coordinate
     *
     * @var int
     */
    private $yStart = 0;

    /**
     * The line's end y coordinate
     *
     * @var int
     */
    private $yEnd = 0;

    /**
     * Create a line object starting at the first coordinate and ending at the second one
     *
     * @param int $x1   The line's start x coordinate
     * @param int $y1   The line's start y coordinate
     * @param int $x2   The line's end x coordinate
     * @param int $y2   The line's end y coordinate
     */
    public function __construct($x1, $y1, $x2, $y2)
    {
        $this->xStart = $x1;
        $this->xEnd = $x2;
        $this->yStart = $y1;
        $this->yEnd = $y2;
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
        list($x1, $y1) = $ctx->toAbsolute($this->xStart, $this->yStart);
        list($x2, $y2) = $ctx->toAbsolute($this->xEnd, $this->yEnd);
        $line = $doc->createElement('line');
        $line->setAttribute('x1', $x1);
        $line->setAttribute('x2', $x2);
        $line->setAttribute('y1', $y1);
        $line->setAttribute('y2', $y2);
        $line->setAttribute('style', $this->getStyle());
        $this->applyAttributes($line);
        return $line;
    }
}
