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

namespace Icinga\Chart;

/**
 * Provide a set of colors that will be used by the chart as default values
 */
class Palette
{
    /**
     * Neutral colors without special meaning
     */
    const NEUTRAL = 'neutral';

    /**
     * A set of problem (i.e. red) colors
     */
    const PROBLEM = 'problem';

    /**
     * A set of ok (i.e. green) colors
     */
    const OK = 'ok';

    /**
     * A set of warning (i.e. yellow) colors
     */
    const WARNING = 'warning';

    /**
     * The colorsets for specific categories
     *
     * @var array
     */
    public $colorSets = array(
        self::OK      => array('#00FF00'),
        self::PROBLEM => array('#FF0000'),
        self::WARNING => array('#FFFF00'),
        self::NEUTRAL => array('#f3f3f3')
    );

    /**
     * Return the next available color as an hex string for the given type
     *
     * @param   string $type    The type to receive a color from
     *
     * @return  string          The color in hex format
     */
    public function getNext($type = self::NEUTRAL)
    {
        if (!isset($this->colorSets[$type])) {
            $type = self::NEUTRAL;
        }

        $color = current($this->colorSets[$type]);
        if ($color === false) {
            reset($this->colorSets[$type]);

            $color = current($this->colorSets[$type]);
        }
        next($this->colorSets[$type]);
        return $color;
    }
}
