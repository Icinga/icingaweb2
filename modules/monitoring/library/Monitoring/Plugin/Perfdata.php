<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Plugin;

use Icinga\Util\Format;
use InvalidArgumentException;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Widget\Chart\InlinePie;
use Icinga\Module\Monitoring\Object\Service;
use Zend_Controller_Front;

class Perfdata
{
    const PERFDATA_OK = 'ok';
    const PERFDATA_WARNING = 'warning';
    const PERFDATA_CRITICAL = 'critical';

    /**
     * The performance data value being parsed
     *
     * @var string
     */
    protected $perfdataValue;

    /**
     * Unit of measurement (UOM)
     *
     * @var string
     */
    protected $unit;

    /**
     * The label
     *
     * @var string
     */
    protected $label;

    /**
     * The value
     *
     * @var float
     */
    protected $value;

    /**
     * The minimum value
     *
     * @var float
     */
    protected $minValue;

    /**
     * The maximum value
     *
     * @var float
     */
    protected $maxValue;

    /**
     * The WARNING threshold
     *
     * TODO: Should be parsed Range-Object instead of string
     *
     * @var string
     */
    protected $warningThreshold;

    /**
     * The CRITICAL threshold
     *
     * TODO: Should be parsed Range-Object instead of string
     *
     * @var string
     */
    protected $criticalThreshold;

    /**
     * Create a new Perfdata object based on the given performance data label and value
     *
     * @param   string      $label      The perfdata label
     * @param   string      $value      The perfdata value
     */
    public function __construct($label, $value)
    {
        $this->perfdataValue = $value;
        $this->label = $label;
        $this->parse();

        if ($this->unit === '%') {
            if ($this->minValue === null) {
                $this->minValue = 0.0;
            }
            if ($this->maxValue === null) {
                $this->maxValue = 100.0;
            }
        }
    }

    /**
     * Return a new Perfdata object based on the given performance data key=value pair
     *
     * @param   string      $perfdata       The key=value pair to parse
     *
     * @return  Perfdata
     *
     * @throws  InvalidArgumentException    In case the given performance data has no content or a invalid format
     */
    public static function fromString($perfdata)
    {
        if (empty($perfdata)) {
            throw new InvalidArgumentException('Perfdata::fromString expects a string with content');
        } elseif (false === strpos($perfdata, '=')) {
            throw new InvalidArgumentException(
                'Perfdata::fromString expects a key=value formatted string. Got "' . $perfdata . '" instead'
            );
        }

        list($label, $value) = explode('=', $perfdata, 2);
        return new static(trim($label), trim($value));
    }

    /**
     * Return whether this performance data's value is a number
     *
     * @return  bool    True in case it's a number, otherwise False
     */
    public function isNumber()
    {
        return $this->unit === null;
    }

    /**
     * Return whether this performance data's value are seconds
     *
     * @return  bool    True in case it's seconds, otherwise False
     */
    public function isSeconds()
    {
        return in_array($this->unit, array('s', 'ms', 'us'));
    }

    /**
     * Return whether this performance data's value is in percentage
     *
     * @return  bool    True in case it's in percentage, otherwise False
     */
    public function isPercentage()
    {
        return $this->unit === '%';
    }

    /**
     * Return whether this performance data's value is in bytes
     *
     * @return  bool    True in case it's in bytes, otherwise False
     */
    public function isBytes()
    {
        return in_array($this->unit, array('b', 'kb', 'mb', 'gb', 'tb'));
    }

    /**
     * Return whether this performance data's value is a counter
     *
     * @return  bool    True in case it's a counter, otherwise False
     */
    public function isCounter()
    {
        return $this->unit === 'c';
    }

    /**
     * Returns whether it is possible to display a visual representation
     *
     * @return  bool    True when the perfdata is visualizable
     */
    public function isVisualizable()
    {
        return isset($this->minValue) && isset($this->maxValue) && isset($this->value);
    }

    /**
     * Return this perfomance data's label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Return the value or null if it is unknown (U)
     *
     * @return  null|float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Return the value as percentage (0-100)
     *
     * @return  null|float
     */
    public function getPercentage()
    {
        if ($this->isPercentage()) {
            return $this->value;
        }

        if ($this->maxValue !== null) {
            $minValue = $this->minValue !== null ? $this->minValue : 0;
            if ((float) ($this->maxValue - $minValue) === 0.0) {
                return null;
            }

            if ($this->value > $minValue) {
                return (($this->value - $minValue) / ($this->maxValue - $minValue)) * 100;
            }
        }
    }

    /**
     * Return this performance data's warning treshold or null if it is not available
     *
     * @return  null|string
     */
    public function getWarningThreshold()
    {
        return $this->warningThreshold;
    }

    /**
     * Return this performance data's critical treshold or null if it is not available
     *
     * @return  null|string
     */
    public function getCriticalThreshold()
    {
        return $this->criticalThreshold;
    }

    /**
     * Return the minimum value or null if it is not available
     *
     * @return  null|string
     */
    public function getMinimumValue()
    {
        return $this->minValue;
    }

    /**
     * Return the maximum value or null if it is not available
     *
     * @return  null|float
     */
    public function getMaximumValue()
    {
        return $this->maxValue;
    }

    /**
     * Return this performance data as string
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->formatLabel();
    }

    /**
     * Parse the current performance data value
     *
     * @todo    Handle optional min/max if UOM == %
     */
    protected function parse()
    {
        $parts = explode(';', $this->perfdataValue);

        $matches = array();
        if (preg_match('@^(-?\d+(\.\d+)?)([a-zA-Z%]{1,2})$@', $parts[0], $matches)) {
            $this->unit = strtolower($matches[3]);
            $this->value = self::convert($matches[1], $this->unit);
        } else {
            $this->value = self::convert($parts[0]);
        }

        switch (count($parts))
        {
            case 5:
                if ($parts[4] !== '') {
                    $this->maxValue = self::convert($parts[4], $this->unit);
                }
            case 4:
                if ($parts[3] !== '') {
                    $this->minValue = self::convert($parts[3], $this->unit);
                }
            case 3:
                $this->criticalThreshold = trim($parts[2]) ? trim($parts[2]) : null;
            case 2:
                $this->warningThreshold = trim($parts[1]) ? trim($parts[1]) : null;
        }
    }

    /**
     * Return the given value converted to its smallest supported representation
     *
     * @param   string      $value      The value to convert
     * @param   string      $fromUnit   The unit the value currently represents
     *
     * @return  null|float              Null in case the value is not a number
     */
    protected static function convert($value, $fromUnit = null)
    {
        if (is_numeric($value)) {
            switch ($fromUnit)
            {
                case 'us':
                    return $value / pow(10, 6);
                case 'ms':
                    return $value / pow(10, 3);
                case 'tb':
                    return floatval($value) * pow(2, 40);
                case 'gb':
                    return floatval($value) * pow(2, 30);
                case 'mb':
                    return floatval($value) * pow(2, 20);
                case 'kb':
                    return floatval($value) * pow(2, 10);
                default:
                    return (float) $value;
            }
        }
    }

    protected function calculatePieChartData()
    {
        $rawValue = $this->getValue();
        $minValue = $this->getMinimumValue() !== null ? $this->getMinimumValue() : 0;
        $maxValue = $this->getMaximumValue();
        $usedValue = ($rawValue - $minValue);
        $unusedValue = ($maxValue - $minValue) - $usedValue;

        $warningThreshold = $this->convert($this->warningThreshold, $this->unit);
        $criticalThreshold = $this->convert($this->criticalThreshold, $this->unit);

        $gray = $unusedValue;
        $green = $orange = $red = 0;

        $pieState = self::PERFDATA_OK;
        if ($warningThreshold > $criticalThreshold) {
            // inverted threshold parsing OK > warning > critical
            if (isset($warningThreshold) && $this->value <= $warningThreshold) {
                $pieState = self::PERFDATA_WARNING;
            }
            if (isset($criticalThreshold) && $this->value <= $criticalThreshold) {
                $pieState = self::PERFDATA_CRITICAL;
            }

        } else {
            // TODO: Use standard perfdata range format to decide the state #8194

            // regular threshold parsing  OK < warning < critical
            if (isset($warningThreshold) && $rawValue > $warningThreshold) {
                $pieState = self::PERFDATA_WARNING;
            }
            if (isset($criticalThreshold) && $rawValue > $criticalThreshold) {
                $pieState = self::PERFDATA_CRITICAL;
            }
        }

        switch ($pieState) {
            case self::PERFDATA_OK:
                $green = $usedValue;
                break;

            case self::PERFDATA_CRITICAL:
                $red = $usedValue;
                break;

            case self::PERFDATA_WARNING:
                $orange = $usedValue;
                break;
        }

        return array($green, $orange, $red, $gray);
    }


    public function asInlinePie()
    {
        if (! $this->isVisualizable()) {
            throw new ProgrammingError('Cannot calculate piechart data for unvisualizable perfdata entry.');
        }

        $data = $this->calculatePieChartData();
        $pieChart = new InlinePie($data, $this);
        $pieChart->setColors(array('#44bb77', '#ffaa44', '#ff5566', '#ddccdd'));

        if (Zend_Controller_Front::getInstance()->getRequest()->isXmlHttpRequest()) {
            $pieChart->disableNoScript();
        }
        return $pieChart;
    }

    /**
     * Format the given value depending on the currently used unit
     */
    protected function format($value)
    {
        if ($this->isPercentage()) {
            return (string)$value . '%';
        }
        if ($this->isBytes()) {
            return Format::bytes($value);
        }
        if ($this->isSeconds()) {
            return Format::seconds($value);
        }
        return number_format($value, 2);
    }

    /**
     * Format the title string that represents this perfdata set
     *
     * @param bool $html
     *
     * @return string
     */
    public function formatLabel($html = false)
    {
        return sprintf(
            $html ? '<b>%s %s</b> (%s%%)' : '%s %s (%s%%)',
            htmlspecialchars($this->getLabel()),
            $this->format($this->value),
            number_format($this->getPercentage(), 2)
        );
    }

    public function toArray()
    {
        $parts = array(
            'label' => $this->getLabel(),
            'value' => $this->format($this->getvalue()),
            'min' => isset($this->minValue) && !$this->isPercentage() ? $this->format($this->minValue) : '',
            'max' => isset($this->maxValue) && !$this->isPercentage() ? $this->format($this->maxValue) : '',
            'warn' => isset($this->warningThreshold) ? $this->format(self::convert($this->warningThreshold, $this->unit)) : '',
            'crit' => isset($this->criticalThreshold) ? $this->format(self::convert($this->criticalThreshold, $this->unit)) : ''
        );
        return $parts;
    }

    /**
     * Return the state indicated by this perfdata
     *
     * @see Service
     *
     * @return int
     */
    public function getState()
    {
        if ($this->value === null) {
            return Service::STATE_UNKNOWN;
        }

        if (! ($this->criticalThreshold === null
            || $this->value < $this->criticalThreshold)) {
            return Service::STATE_CRITICAL;
        }

        if (! ($this->warningThreshold === null
            || $this->value < $this->warningThreshold)) {
            return Service::STATE_WARNING;
        }

        return Service::STATE_OK;
    }

    /**
     * Return whether the state indicated by this perfdata is worse than
     * the state indicated by the other perfdata
     * CRITICAL > UNKNOWN > WARNING > OK
     *
     * @param Perfdata $rhs     the other perfdata
     *
     * @return bool
     */
    public function worseThan(Perfdata $rhs)
    {
        if (($state = $this->getState()) === ($rhsState = $rhs->getState())) {
            return $this->getPercentage() > $rhs->getPercentage();
        }

        if ($state === Service::STATE_CRITICAL) {
            return true;
        }

        if ($state === Service::STATE_UNKNOWN) {
            return $rhsState !== Service::STATE_CRITICAL;
        }

        if ($state === Service::STATE_WARNING) {
            return $rhsState === Service::STATE_OK;
        }

        return false;
    }
}
