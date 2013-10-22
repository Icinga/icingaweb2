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

namespace Icinga\Timeline;

use \DateInterval;
use \Icinga\Web\Form;

/**
 * Represents a set of events in a specific time range
 *
 * @see Form
 */
class TimeLine extends Form
{
    /**
     * The range of time represented by this timeline
     *
     * @var TimeRange
     */
    private $range;

    /**
     * The event groups this timeline will display
     *
     * @var array
     */
    private $displayData;

    /**
     * The event groups this timeline uses to calculate forecasts
     *
     * @var array
     */
    private $forecastData;

    /**
     * Whether only the timeline is shown
     *
     * @var bool
     */
    private $hideOuterElements = false;

    /**
     * Set the range of time to represent
     *
     * @param   TimeRange   $range      The range of time to represent
     */
    public function setTimeRange(TimeRange $range)
    {
        $this->range = $range;
    }

    /**
     * Set the groups this timeline should display
     *
     * @param   array   $entries    The TimeEntry objects
     */
    public function setDisplayData(array $entries)
    {
        $this->displayData = $entries;
    }

    /**
     * Set the groups this timeline should use to calculate forecasts
     *
     * @param   array   $entries    The TimeEntry objects
     */
    public function setForecastData(array $entries)
    {
        $this->forecastData = $entries;
    }

    /**
     * Define that only the timeline itself should be rendered
     */
    public function showLineOnly()
    {
        $this->hideOuterElements = true;
    }

    /**
     * Return the chosen interval
     *
     * @return  DateInterval    The chosen interval
     * @throws  Exception       If an invalid interval is given in the current request
     */
    public function getInterval()
    {
        switch ($this->getRequest()->getPost('interval', '4h'))
        {
            case '4h':
                return new DateInterval('PT4H');
            case '1d':
                return new DateInterval('P1D');
            case '1w':
                return new DateInterval('P1W');
            case '1m':
                return new DateInterval('P1M');
            case '1y':
                return new DateInterval('P1Y');
            default:
                throw new Exception('Invalid interval given in request');
        }
    }

    /**
     * Disable the CSRF token
     */
    public function init()
    {
        $this->setTokenDisabled();
    }

    /**
     * Build the timeline
     */
    public function create()
    {
        
    }
}
