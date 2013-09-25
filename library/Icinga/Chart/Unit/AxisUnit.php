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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Chart\Unit;

use \Iterator;

/**
 * Base class for Axis Units
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
}
