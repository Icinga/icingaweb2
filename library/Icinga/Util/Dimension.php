<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 8/6/13
 * Time: 11:13 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Icinga\Util;

use InvalidArgumentException;

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
     * Creates a new Dimension object with the given size and unit
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
     * Returns true when the value is > 0
     *
     * @return bool
     */
    public function isDefined()
    {
        return $this->value > 0;
    }

    /**
     * Returns the underlying value without unit information
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns this value with it's according unit as a string
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

    public static function fromString($string)
    {
        $matches = array();
        if (!preg_match_all('/^ *([0-9]+)(px|pt|em|\%) */i', $string, $matches)) {
            throw new InvalidArgumentException($string.' is not a valid dimension');
        }
        return new Dimension(intval($matches[1]), $matches[2]);
    }
}