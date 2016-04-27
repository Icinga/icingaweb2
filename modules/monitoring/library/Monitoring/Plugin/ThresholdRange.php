<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Plugin;

/**
 * The warning/critical threshold of a measured value
 */
class ThresholdRange
{
    /**
     * The smallest value inside the range (null stands for -∞)
     *
     * @var float|null
     */
    protected $min;

    /**
     * The biggest value inside the range (null stands for ∞)
     *
     * @var float|null
     */
    protected $max;

    /**
     * Whether to invert the result of contains()
     *
     * @var bool
     */
    protected $inverted = false;

    /**
     * Create a new instance based on a threshold range conforming to <https://nagios-plugins.org/doc/guidelines.html>
     *
     * @param   string  $rawRange
     *
     * @return  ThresholdRange
     */
    public static function fromString($rawRange)
    {
        $range = new static();

        $rawRange = ltrim($rawRange);
        if (substr($rawRange, 0, 1) === '@') {
            $range->setInverted(true);
            $rawRange = substr($rawRange, 1);
        }

        if (strpos($rawRange, ':') === false) {
            $min = 0.0;
            $max = trim(floatval($rawRange));
        } else {
            list($min, $max) = explode(':', $rawRange, 2);
            $min = trim($min);
            $max = trim($max);

            switch ($min) {
                case '':
                    $min = 0.0;
                    break;
                case '~':
                    $min = null;
                    break;
                default:
                    $min = floatval($min);
            }

            $max = empty($max) ? null : floatval($max);
        }

        return $range->setMin($min)
            ->setMax($max);
    }

    /**
     * Set the smallest value inside the range (null stands for -∞)
     *
     * @param   float|null  $min
     *
     * @return  $this
     */
    public function setMin($min)
    {
        $this->min = $min;
        return $this;
    }

    /**
     * Get the smallest value inside the range (null stands for -∞)
     *
     * @return  float|null
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set the biggest value inside the range (null stands for ∞)
     *
     * @param   float|null  $max
     *
     * @return  $this
     */
    public function setMax($max)
    {
        $this->max = $max;
        return $this;
    }

    /**
     * Get the biggest value inside the range (null stands for ∞)
     *
     * @return  float|null
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set whether to invert the result of contains()
     *
     * @param   bool    $inverted
     *
     * @return  $this
     */
    public function setInverted($inverted)
    {
        $this->inverted = $inverted;
        return $this;
    }

    /**
     * Get whether to invert the result of contains()
     *
     * @return  bool
     */
    public function isInverted()
    {
        return $this->inverted;
    }

    /**
     * Return whether $value is inside $this
     *
     * @param   float   $value
     *
     * @return  bool
     */
    public function contains($value)
    {
        return (bool) ($this->inverted ^ (
            ($this->min === null || $this->min <= $value) && ($this->max === null || $this->max >= $value)
        ));
    }

    /**
     * Return the textual representation of $this, suitable for fromString()
     *
     * @return  string
     */
    public function __toString()
    {
        if ($this->min === null) {
            $res = '~:';
        } else {
            $res = $this->min === 0.0 ? '' : $this->min . ':';
        }
        $res .= $this->max === null ? '' : $this->max;

        return ($this->inverted ? '@' : '') . (empty($res) ? '0:' : $res);
    }
}
