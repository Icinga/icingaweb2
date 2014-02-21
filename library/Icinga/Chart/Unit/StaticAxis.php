<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}


namespace Icinga\Chart\Unit;


class StaticAxis implements AxisUnit
{
    private $items = array();

    /**
     * Add a dataset to this AxisUnit, required for dynamic min and max vlaues
     *
     * @param array $dataset    The dataset that will be shown in the Axis
     * @param int $id           The idx in the dataset (0 for x, 1 for y)
     */
    public function addValues(array $dataset, $idx = 0)
    {
        $datapoints = array();
        foreach ($dataset['data'] as $points) {
            $this->items[] = $points[$idx];
        }
        $this->items = array_unique($this->items);

        return $this;
    }

    /**
     * Transform the given absolute value in an axis relative value
     *
     * @param   int $value The absolute, dataset dependent value
     *
     * @return  int        An axis relative value
     */
    public function transform($value)
    {
        $flipped = array_flip($this->items);
        if (!isset($flipped[$value])) {
            return 0;
        }
        $pos = $flipped[$value];
        return 1 + (99 / count($this->items) * $pos);
    }
    /**
     * Set the axis minimum value to a fixed value
     *
     * @param int $min The new minimum value
     */
    public function setMin($min)
    {
    }

    /**
     * Set the axis maximum value to a fixed value
     *
     * @param int $max The new maximum value
     */
    public function setMax($max)
    {
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return  1 + (99 / count($this->items) * key($this->items));
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        return next($this->items);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return current($this->items);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return current($this->items) !== false;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        return reset($this->items);
    }
}
