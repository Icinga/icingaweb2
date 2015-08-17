<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Unit;

/**
 * Logarithmic tick distribution over the axis
 *
 * This class does not use the actual logarithm, but a slightly altered version called the
 * Log-Modulo transformation. This is necessary, since a regular logarithmic scale is not able to display negative
 * values and zero-points. See <a href="http://blogs.sas.com/content/iml/2014/07/14/log-transformation-of-pos-neg>
 * this article </a> for a more detailed description.
 */
class LogarithmicUnit implements AxisUnit
{
    /**
     * @var int
     */
    protected $base;

    /**
     * @var
     */
    protected $currentTick;

    /**
     * @var
     */
    protected $minExp;

    /**
     * @var
     */
    protected $maxExp;

    /**
     * True when the minimum value is static and isn't affected by the data set
     *
     * @var bool
     */
    protected $staticMin = false;

    /**
     * True when the maximum value is static and isn't affected by the data set
     *
     * @var bool
     */
    protected $staticMax = false;

    /**
     * Create and initialize this AxisUnit
     *
     * @param int $nrOfTicks The number of ticks to use
     */
    public function __construct($base = 10)
    {;
        $this->base = $base;
        $this->minExp = PHP_INT_MAX;
        $this->maxExp = ~PHP_INT_MAX;
    }

    /**
     * Add a dataset and calculate the minimum and maximum value for this AxisUnit
     *
     * @param   array $dataset  The dataset to add
     * @param   int $idx        The idx (0 for x, 1 for y)
     *
     * @return  $this            Fluent interface
     */
    public function addValues(array $dataset, $idx = 0)
    {
        $datapoints = array();

        foreach ($dataset['data'] as $points) {
            $datapoints[] = $points[$idx];
        }
        if (empty($datapoints)) {
            return $this;
        }
        sort($datapoints);
        if (!$this->staticMax) {
            $this->maxExp = max($this->maxExp, $this->logCeil($datapoints[count($datapoints) - 1]));
        }
        if (!$this->staticMin) {
            $this->minExp = min($this->minExp, $this->logFloor($datapoints[0]));
        }
        $this->currentTick = 0;

        return $this;
    }

    /**
     * Transform the absolute value to an axis relative value
     *
     * @param   int $value  The absolute coordinate from the data set
     * @return  float|int   The axis relative coordinate (between 0 and 100)
     */
    public function transform($value)
    {
        if ($value < $this->pow($this->minExp)) {
            return 0;
        } elseif ($value > $this->pow($this->maxExp)) {
            return 100;
        } else {
            return 100 * ($this->log($value) - $this->minExp) / $this->getTicks();
        }
    }

    /**
     * Return the position of the current tick
     *
     * @return int
     */
    public function current()
    {
        return $this->currentTick * (100 / $this->getTicks());
    }

    /**
     * Calculate the next tick and tick value
     */
    public function next()
    {
        ++ $this->currentTick;
    }

    /**
     * Return the label for the current tick
     *
     * @return string The label for the current tick
     */
    public function key()
    {
        $currentBase = $this->currentTick + $this->minExp;
        if (abs($currentBase) > 4) {
            return $this->base . 'E' . $currentBase;
        }
        return (string) intval($this->pow($currentBase));
    }

    /**
     * True when we're at a valid tick (iterator interface)
     *
     * @return bool
     */
    public function valid()
    {
        return $this->currentTick >= 0 && $this->currentTick < $this->getTicks();
    }

    /**
     * Reset the current tick and label value
     */
    public function rewind()
    {
        $this->currentTick = 0;
    }

    /**
     * Perform a log-modulo transformation
     *
     * @param $value    The value to transform
     *
     * @return double   The transformed value
     */
    protected function log($value)
    {
        $sign = $value > 0 ? 1 : -1;
        return $sign * log1p($sign * $value) / log($this->base);
    }

    /**
     * Calculate the biggest exponent necessary to display the given data point
     *
     * @param $value
     *
     * @return float
     */
    protected function logCeil($value)
    {
        return ceil($this->log($value)) + 1;
    }

    /**
     * Calculate the smallest exponent necessary to display the given data point
     *
     * @param $value
     *
     * @return float
     */
    protected function logFloor($value)
    {
        return floor($this->log($value));
    }

    /**
     * Inverse function to the log-modulo transformation
     *
     * @param $value
     *
     * @return double
     */
    protected function pow($value)
    {
        if ($value == 0) {
            return 0;
        }
        $sign = $value > 0 ? 1 : -1;
        return $sign * (pow($this->base, $sign * $value));
    }

    /**
     * Set the axis minimum value to a fixed value
     *
     * @param int $min The new minimum value
     */
    public function setMin($min)
    {
        $this->minExp = $this->logFloor($min);
        $this->staticMin = true;
    }

    /**
     * Set the axis maximum value to a fixed value
     *
     * @param int $max The new maximum value
     */
    public function setMax($max)
    {
        $this->maxExp = $this->logCeil($max);
        $this->staticMax = true;
    }

    /**
     * Return the current minimum value of the axis
     *
     * @return int The minimum set for this axis
     */
    public function getMin()
    {
        return $this->pow($this->minExp);
    }

    /**
     * Return the current maximum value of the axis
     *
     * @return int The maximum set for this axis
     */
    public function getMax()
    {
        return $this->pow($this->maxExp);
    }

    /**
     * Get the amount of ticks necessary to display this AxisUnit
     *
     * @return int
     */
    public function getTicks()
    {
        return $this->maxExp - $this->minExp;
    }
}
