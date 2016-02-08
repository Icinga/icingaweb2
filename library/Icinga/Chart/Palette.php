<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

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
