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


class LinearAxis implements AxisUnit {

    private $min;
    private $max;

    private $staticMin = false;
    private $staticMax = false;

    private $nrOfTicks = 10;

    private $currentTick = 0;
    private $currentValue = 0;

    public function __construct($nrOfTicks = 10) {
        $this->min = PHP_INT_MAX;
        $this->max = ~PHP_INT_MAX;
        $this->nrOfTicks = $nrOfTicks;
    }

    public function addValues(array $dataset, $idx=0)
    {

        $datapoints = array();
        foreach($dataset['data'] as $points) {
            $datapoints[] = $points[$idx];
        }
        sort($datapoints);
        if (!$this->staticMax) {
            $this->max = max($this->max, $datapoints[count($datapoints)-1]);
        }
        if (!$this->staticMin) {
            $this->min = min($this->min, $datapoints[0]);
        }

        $this->currentTick = 0;
        $this->currentValue = $this->min;
        return $this;
    }

    public function transform($value)
    {
        if ($value < $this->min) {
            return 0;
        } else if ($value > $this->max) {
            return 100;
        } else {
            return 100 * ($value - $this->min) / ($this->max - $this->min);
        }
    }

    public function current()
    {
        return $this->currentTick;
    }


    public function next()
    {
        $this->currentTick += (100 / $this->nrOfTicks);
        $this->currentValue += (($this->max - $this->min) / $this->nrOfTicks );
    }


    public function key()
    {
        return $this->currentValue;
    }


    public function valid()
    {
        return $this->currentTick >= 0 && $this->currentTick <= 100;
    }

    public function rewind()
    {
        $this->currentTick = 0;
        $this->currentValue = $this->min;
    }

    /**
     * @param int $max
     */
    public function setMax($max)
    {
        if ($max !== null) {
            $this->max = $max;
            $this->staticMax = true;
        }
    }

    /**
     * @param int $min
     */
    public function setMin($min)
    {
        if ($min !== null) {
            $this->min = $min;
            $this->staticMin = true;
        }
    }

}