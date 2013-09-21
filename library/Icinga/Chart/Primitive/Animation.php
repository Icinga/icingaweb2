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

use DOMElement;
use Icinga\Chart\Render\RenderContext;

/**
 * Drawable for the SVG animate tag
 */
class Animation implements Drawable
{
    /**
     * The attribute to animate
     * @var string
     */
    private $attribute;

    /**
     * The 'from' value
     *
     * @var mixed
     */
    private $from;

    /**
     * The to value
     *
     * @var mixed
     */
    private $to;

    /**
     * The begin value (in seconds)
     *
     * @var float
     */
    private $begin = 0;

    /**
     * The duration value (in seconds)
     *
     * @var float
     */
    private $duration = 0.5;

    /**
     * Create an animation object
     *
     * @param string $attribute     The attribute to animate
     * @param string $from          The from value for the animation
     * @param string $to            The to value for the animation
     * @param float  $duration      The duration of the duration
     * @param float  $begin         The begin of the duration
     */
    public function __construct($attribute, $from, $to, $duration = 0.5, $begin = 0)
    {
        $this->attribute = $attribute;
        $this->from = $from;
        $this->to = $to;
        $this->duration = $duration;
        $this->begin = $begin;
    }

    /**
     * Create the SVG representation from this Drawable
     *
     * @param RenderContext $ctx    The context to use for rendering
     * @return DOMElement           The SVG Element
     */
    public function toSvg(RenderContext $ctx)
    {

        $animate = $ctx->getDocument()->createElement('animate');
        $animate->setAttribute('attributeName', $this->attribute);
        $animate->setAttribute('attributeType', 'XML');
        $animate->setAttribute('from', $this->from);
        $animate->setAttribute('to', $this->to);
        $animate->setAttribute('begin', $this->begin . 's');
        $animate->setAttribute('dur', $this->duration . 's');
        $animate->setAttributE('fill', "freeze");

        return $animate;
    }
}
