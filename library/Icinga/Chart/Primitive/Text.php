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
use DOMText;

class Text extends Styleable implements Drawable
{
    const ALIGN_END     = 'end';
    const ALIGN_START   = 'start';
    const ALIGN_MIDDLE  = 'middle';


    const ORIENTATION_HORIZONTAL = "";
    const ORIENTATION_VERTICAL = "writing-mode: tb;";

    private $x;
    private $y;
    private $text;
    private $fontSize = '1.5em';
    public  $fill = '#000';
    private $alignment = self::ALIGN_START;

    public function __construct($x, $y, $text, $fontSize = '1.5em')
    {
        $this->x = $x;
        $this->y = $y;
        $this->text = $text;
    }

    public function setFontSize($size)
    {
        $this->fontSize = $size;
        return $this;
    }

    public function setAlignment($align)
    {
        $this->alignment = $align;
        return $this;
    }


    public function toSvg(RenderContext $ctx)
    {
        list($x, $y) = $ctx->toAbsolute($this->x, $this->y);
        $text = $ctx->getDocument()->createElement('text');

        $text->setAttribute('x', $x-15);
        $text->setAttribute('style', $this->getStyle() . ';font-size:' . $this->fontSize . '; font-family: Verdana, serif;'
             .  'font-weight: normal; font-style: normal;'
             .  'text-anchor: ' . $this->alignment);
        $text->setAttribute('y', $y);
        $text->appendChild(new DOMText($this->text));

        return $text;
    }
}