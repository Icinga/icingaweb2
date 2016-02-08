<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Unit;

use Iterator;

/**
 * Base class for Axis Units
 *
 * An AxisUnit takes a set of values and places them on a given range
 *
 * Concrete subclasses must implement the iterator interface, with
 * getCurrent returning the axis relative position and getValue the label
 * that will be displayed
 */
interface AxisUnit extends Iterator
{
    /**
     * Add a dataset to this AxisUnit, required for dynamic min and max vlaues
     *
     * @param array $dataset    The dataset that will be shown in the Axis
     * @param int $id           The idx in the dataset (0 for x, 1 for y)
     */
    public function addValues(array $dataset, $id = 0);

    /**
     * Transform the given absolute value in an axis relative value
     *
     * @param   int $value The absolute, dataset dependent value
     *
     * @return  int        An axis relative value
     */
    public function transform($value);

    /**
     * Set the axis minimum value to a fixed value
     *
     * @param int $min The new minimum value
     */
    public function setMin($min);

    /**
     * Set the axis maximum value to a fixed value
     *
     * @param int $max The new maximum value
     */
    public function setMax($max);

    /**
     * Get the amount of ticks of this axis
     *
     * @return int
     */
    public function getTicks();
}
