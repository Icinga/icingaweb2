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
     * @var ThresholdRange
     */
    protected $warningThreshold;

    /**
     * The CRITICAL threshold
     *
     * @var ThresholdRange
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

        $warn = $this->warningThreshold->getMax();
        if ($warn !== null) {
            $crit = $this->criticalThreshold->getMax();
            if ($crit !== null && $warn > $crit) {
                $this->warningThreshold->setInverted();
                $this->criticalThreshold->setInverted();
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
        } elseif (strpos($perfdata, '=') === false) {
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
            $minValue = $this->minValue !== null ? $this->minValue : 0.0;
            if ($this->maxValue == $minValue) {
                return null;
            }

            if ($this->value > $minValue) {
                return (($this->value - $minValue) / ($this->maxValue - $minValue)) * 100;
            }
        }
    }

    /**
     * Return this performance data's warning treshold
     *
     * @return  ThresholdRange
     */
    public function getWarningThreshold()
    {
        return $this->warningThreshold;
    }

    /**
     * Return this performance data's critical treshold
     *
     * @return  ThresholdRange
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

        switch (count($parts)) {
            /* @noinspection PhpMissingBreakStatementInspection */
            case 5:
                if ($parts[4] !== '') {
                    $this->maxValue = self::convert($parts[4], $this->unit);
                }
            /* @noinspection PhpMissingBreakStatementInspection */
            case 4:
                if ($parts[3] !== '') {
                    $this->minValue = self::convert($parts[3], $this->unit);
                }
            /* @noinspection PhpMissingBreakStatementInspection */
            case 3:
                $this->criticalThreshold = self::convert(
                    ThresholdRange::fromString(trim($parts[2])),
                    $this->unit
                );
                // Fallthrough
            case 2:
                $this->warningThreshold = self::convert(
                    ThresholdRange::fromString(trim($parts[1])),
                    $this->unit
                );
        }

        if ($this->warningThreshold === null) {
            $this->warningThreshold = new ThresholdRange();
        }
        if ($this->criticalThreshold === null) {
            $this->criticalThreshold = new ThresholdRange();
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
        if ($value instanceof ThresholdRange) {
            $value = clone $value;

            $min = $value->getMin();
            if ($min !== null) {
                $value->setMin(self::convert($min, $fromUnit));
            }

            $max = $value->getMax();
            if ($max !== null) {
                $value->setMax(self::convert($max, $fromUnit));
            }

            return $value;
        }

        if (is_numeric($value)) {
            switch ($fromUnit) {
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
        $usedValue = ($rawValue - $minValue);

        $green = $orange = $red = 0;

        if ($this->criticalThreshold->contains($rawValue)) {
            if ($this->warningThreshold->contains($rawValue)) {
                $green = $usedValue;
            } else {
                $orange = $usedValue;
            }
        } else {
            $red = $usedValue;
        }

        return array($green, $orange, $red, ($this->getMaximumValue() - $minValue) - $usedValue);
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
        if ($value instanceof ThresholdRange) {
            if ($value->getMin()) {
                return (string) $value;
            }

            $max = $value->getMax();
            return $max === null ? '' : $this->format($max);
        }

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
        return array(
            'label' => $this->getLabel(),
            'value' => $this->format($this->getvalue()),
            'min' => isset($this->minValue) && !$this->isPercentage()
                ? $this->format($this->minValue)
                : '',
            'max' => isset($this->maxValue) && !$this->isPercentage()
                ? $this->format($this->maxValue)
                : '',
            'warn' => $this->format($this->warningThreshold),
            'crit' => $this->format($this->criticalThreshold)
        );
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

        if (! $this->criticalThreshold->contains($this->value)) {
            return Service::STATE_CRITICAL;
        }

        if (! $this->warningThreshold->contains($this->value)) {
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
