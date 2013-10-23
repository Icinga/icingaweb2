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

namespace Icinga\Util;

class Dimension
{
    /**
     * Defines this dimension as nr of pixels
     */
    const UNIT_PX = "px";

    /**
     * Defines this dimension as width of 'M' in current font
     */
    const UNIT_EM = "em";

    /**
     * Defines this dimension as a percentage value
     */
    const UNIT_PERCENT = "%";

    /**
     * Defines this dimension in points
     */
    const UNIT_PT = "pt";

    /**
     * The current set value for this dimension
     *
     * @var int
     */
    private $value = 0;

    /**
     * The unit to interpret the value with
     *
     * @var string
     */
    private $unit = self::UNIT_PX;

    /**
     * Create a new Dimension object with the given size and unit
     *
     * @param int $value        The new value
     * @param string $unit      The unit to use (default: px)
     */
    public function __construct($value, $unit = self::UNIT_PX)
    {
        $this->setValue($value, $unit);
    }

    /**
     * Change the value and unit of this dimension
     *
     * @param int $value        The new value
     * @param string $unit      The unit to use (default: px)
     */
    public function setValue($value, $unit = self::UNIT_PX)
    {
        $this->value = intval($value);
        $this->unit = $unit;
    }

    /**
     * Return true when the value is > 0
     *
     * @return bool
     */
    public function isDefined()
    {
        return $this->value > 0;
    }

    /**
     * Return the underlying value without unit information
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Return the unit used for the value
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Return this value with it's according unit as a string
     *
     * @return string
     */
    public function __toString()
    {
        if (!$this->isDefined()) {
            return "";
        }
        return $this->value.$this->unit;
    }

    /**
     * Create a new Dimension object from a string containing the numeric value and the dimension (e.g. 200px, 20%)
     *
     * @param $string                       The string to parse
     *
     * @return Dimension
     */
    public static function fromString($string)
    {
        $matches = array();
        if (!preg_match_all('/^ *([0-9]+)(px|pt|em|\%) */i', $string, $matches)) {
            return new Dimension(0);
        }
        return new Dimension(intval($matches[1][0]), $matches[2][0]);
    }
}
