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

use \DomElement;
use \Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Format;

/**
 * Drawable representing the SVG rect element
 */
class Rect extends Animatable implements Drawable
{
    /**
     * The x position
     *
     * @var int
     */
    private $x;

    /**
     * The y position
     *
     * @var int
     */
    private $y;

    /**
     * The width of this rect
     *
     * @var int
     */
    private $width;

    /**
     * The height of this rect
     *
     * @var int
     */
    private $height;

    /**
     * Whether to keep the ratio
     *
     * @var bool
     */
    private $keepRatio = false;

    /**
     * Create this rect
     *
     * @param int $x        The x position of the rect
     * @param int $y        The y position of the rectangle
     * @param int $width    The width of the rectangle
     * @param int $height   The height of the rectangle
     */
    public function __construct($x, $y, $width, $height)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Call to let the rectangle keep the ratio
     */
    public function keepRatio()
    {
        $this->keepRatio = true;
    }

    /**
     * Create the SVG representation from this Drawable
     *
     * @param   RenderContext $ctx  The context to use for rendering
     *
     * @return  DOMElement          The SVG Element
     */
    public function toSvg(RenderContext $ctx)
    {
        $doc = $ctx->getDocument();
        $rect = $doc->createElement('rect');

        list($x, $y) = $ctx->toAbsolute($this->x, $this->y);
        if ($this->keepRatio) {
            $ctx->keepRatio();
        }
        list($width, $height) = $ctx->toAbsolute($this->width, $this->height);
        if ($this->keepRatio) {
            $ctx->ignoreRatio();
        }
        $rect->setAttribute('x', Format::formatSVGNumber($x));
        $rect->setAttribute('y', Format::formatSVGNumber($y));
        $rect->setAttribute('width', Format::formatSVGNumber($width));
        $rect->setAttribute('height', Format::formatSVGNumber($height));
        $rect->setAttribute('style', $this->getStyle());

        $this->applyAttributes($rect);
        $this->appendAnimation($rect, $ctx);

        return $rect;
    }
}
