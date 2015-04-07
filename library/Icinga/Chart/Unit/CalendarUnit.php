<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */


namespace Icinga\Chart\Unit;

use Icinga\Util\DateTimeFactory;

/**
 * Calendar Axis Unit that transforms timestamps into user-readable values
 *
 */
class CalendarUnit extends LinearUnit
{
    /**
     * Constant for a minute
     */
    const MINUTE    = 60;

    /**
     * Constant for an hour
     */
    const HOUR      = 3600;

    /**
     * Constant for a day
     */
    const DAY       = 864000;

    /**
     * Constant for ~a month
     * 30 Days, this is sufficient for our needs
     */
    const MONTH     = 2592000; // x

    /**
     * An array containing all labels that will be displayed
     *
     * @var array
     */
    private $labels = array();

    /**
     * The date format to use
     *
     * @var string
     */
    private $dateFormat = 'd-m';

    /**
     * The time format to use
     *
     * @var string
     */
    private $timeFormat = 'g:i:s';

    /**
     * Create the labels for the given dataset
     */
    private function createLabels()
    {
        $this->labels = array();
        $duration = $this->getMax() - $this->getMin();

        if ($duration <= self::HOUR) {
            $unit = self::MINUTE;
        } elseif ($duration <= self::DAY) {
            $unit = self::HOUR;
        } elseif ($duration <= self::MONTH) {
            $unit = self::DAY;
        } else {
            $unit = self::MONTH;
        }
        $this->calculateLabels($unit);
    }

    /**
     * Calculate the labels for this dataset
     *
     * @param integer $unit The unit to use as the basis for calculation
     */
    private function calculateLabels($unit)
    {
        $fac = DateTimeFactory::create();

        $duration = $this->getMax() - $this->getMin();

        // Calculate number of ticks, but not more than 30
        $tickCount = ($duration/$unit * 10);
        if ($tickCount > 30) {
            $tickCount = 30;
        }

        $step = $duration / $tickCount;
        $format = $this->timeFormat;
        if ($unit === self::DAY) {
            $format = $this->dateFormat;
        } elseif ($unit === self::MONTH) {
            $format = $this->dateFormat;
        }

        for ($i = 0; $i <= $duration; $i += $step) {
            $this->labels[] = $fac->setTimestamp($this->getMin() + $i)->format($format);
        }
    }

    /**
     * Add a dataset to this CalendarUnit and update labels
     *
     * @param   array   $dataset    The dataset to update
     * @param   int     $idx        The index to use for determining the data
     *
     * @return  $this                Fluid interface
     */
    public function addValues(array $dataset, $idx = 0)
    {
        parent::addValues($dataset, $idx);
        $this->createLabels();
        return $this;
    }

    /**
     * Return the current axis relative position
     *
     * @return int The position of the next tick (between 0 and 100)
     */
    public function current()
    {
        return 100 * (key($this->labels) / count($this->labels));
    }

    /**
     * Move to next tick
     */
    public function next()
    {
        next($this->labels);
    }

    /**
     * Return the current tick caption
     *
     * @return string
     */
    public function key()
    {
        return current($this->labels);
    }

    /**
     * Return true when the iterator is in a valid range
     *
     * @return bool
     */
    public function valid()
    {
        return current($this->labels) !== false;
    }

    /**
     * Rewind the internal array
     */
    public function rewind()
    {
        reset($this->labels);
    }
}
