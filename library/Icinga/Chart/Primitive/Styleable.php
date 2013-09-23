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

/**
 * Base class for stylable drawables
 */
class Styleable
{

    /**
     * The stroke width to use
     * @var int
     */
    public $strokeWidth = 0;

    /**
     * The stroke color to use
     * @var string
     */
    public $strokeColor = '#000';

    /**
     * The fill color to use
     * @var string
     */
    public $fill = 'none';

    /**
     * Additional styles to be appended to the style attribute
     * @var string
     */
    public $additionalStyle = '';

    /**
     * The id of this element
     * @var string
     */
    public $id = null;

    /**
     * Additional attributes to be set
     *
     * @var array
     */
    public $attributes = array();

    /**
     * Set the stroke width for this drawable
     *
     * @param  string  $stroke  The stroke with with unit
     *
     * @return self             Fluid interface
     */
    public function setStrokeWidth($width)
    {
        $this->strokeWidth = $width;
        return $this;
    }

    /**
     * Set the color for the stroke or none for no stroke
     *
     * @param string $color     The color to set for the stroke
     *
     * @return self             Fluid interface
     */
    public function setStrokeColor($color)
    {
        $this->strokeColor = $color ? $color : 'none';
        return $this;
    }

    /**
     * Set additional styles for this drawable
     *
     * @param string $styles    The styles to set additionally
     *
     * @return self             Fluid interface
     */
    public function setAdditionalStyle($styles)
    {
        $this->additionalStyle = $styles;
        return $this;
    }

    /**
     * Set the fill for this styleable
     *
     * @param string $color     The color to use for filling or null to use no fill
     *
     * @return self             Fluid interface
     */
    public function setFill($color = null)
    {
        $this->fill = $color ? $color : 'none';
        return $this;
    }

    /**
     * Set the id for this element
     *
     * @param string $id        The id to set for this element
     *
     * @return self             Fluid interface
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Return the content of the style attribute as a string
     *
     * @return string           T
     */
    public function getStyle()
    {
        $base = sprintf("fill: %s; stroke: %s;stroke-width: %s;", $this->fill, $this->strokeColor, $this->strokeWidth);
        $base .= ';' . $this->additionalStyle . ';';
        return $base;
    }

    /**
     *  Add an additional attribte to this element
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    protected function applyAttributes(DOMElement $el)
    {
        foreach ($this->attributes as $name => $value) {
            $el->setAttribute($name, $value);
        }
    }
}
