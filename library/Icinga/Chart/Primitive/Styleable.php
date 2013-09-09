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


class Styleable {

    public $strokeWidth = 0;
    public $strokeColor = '#000';
    public $fill = 'none';
    public $additionalStyle = '';
    public $opacity = '1';


    /**
     * @param mixed $stroke
     */
    public function setStrokeWidth($width)
    {
        $this->strokeWidth = $width;
        return $this;
    }

    public function setStrokeColor($color)
    {
        $this->strokeColor = $color ? $color : 'none';
        return $this;
    }

    public function setAdditionalStyle($styles)
    {
        $this->additionalStyle = $styles;
        return $this;
    }

    public function setFill($color = null) {
        $this->fill = $color ? $color : 'none';
        return $this;
    }

    public function getStyle()
    {
        $base = sprintf("fill: %s; stroke: %s;stroke-width: %s;", $this->fill, $this->strokeColor, $this->strokeWidth);
        $base .= ';' . $this->additionalStyle . ';';
        return $base;
    }



}