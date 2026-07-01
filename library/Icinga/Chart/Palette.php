<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
    public $colorSets = [
        self::OK      => ['#00FF00'],
        self::PROBLEM => ['#FF0000'],
        self::WARNING => ['#FFFF00'],
        self::NEUTRAL => ['#f3f3f3']
    ];

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
