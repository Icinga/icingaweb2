<?php
// {{{ICINGA_LICENSE_HEADER}}}
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
        self::OK      => array('#00FF00','#00C90D', '#008209', '#238C47', '#00BB3F', '#37DD6F'),
        self::PROBLEM => array('#FF0000','#FF1300', '#FF4E40', '#A60C00', '#FF4500', '#A62D00'),
        self::WARNING => array('#FFFF00', 'B4B400' , '#A6A600', '#F5FF73', '#FFB300', '#BFA730'),
        self::NEUTRAL => array('#232323', '#009999', '#1D7373', '#ACACFF', '#8F9ABF', '#356AA6')
    );

    /**
     * Return the next available color as an hex string for the given type
     *
     * @param string $type  The type to receive a color from
     *
     * @return string       The color in hex format
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
