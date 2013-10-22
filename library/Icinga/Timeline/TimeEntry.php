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

use \DateTime;

/**
 * An event group that is part of a timeline
 */
class TimeEntry
{
    /**
     * The name of this group
     *
     * @var string
     */
    private $name;

    /**
     * The amount of events that are part of this group
     *
     * @var int
     */
    private $value;

    /**
     * The date and time of this group
     *
     * @var DateTime
     */
    private $dateTime;

    /**
     * The url to this group's detail view
     *
     * @var string
     */
    private $detailUrl;

    /**
     * The weight of this group
     *
     * @var float
     */
    private $weight = 1.0;

    /**
     * Initialize a new event group
     *
     * @param   string      $name       The name of the group
     * @param   int         $value      The amount of events
     * @param   DateTime    $dateTime   The date and time of the group
     * @param   string      $detailUrl  The url to the detail view
     */
    public function __construct($name, $value, DateTime $dateTime, $detailUrl)
    {
        $this->detailUrl = $detailUrl;
        $this->dateTime = $dateTime;
        $this->value = $value;
        $this->name = $name;
    }

    /**
     * Return the name of this group
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the amount of events in this group
     *
     * @return  int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Return the date and time of this group
     *
     * @return  DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Return the url to this group's detail view
     *
     * @return  string
     */
    public function getDetailUrl()
    {
        return $this->detailUrl;
    }

    /**
     * Return the weight of this group
     *
     * @return  float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set the weight of this group
     *
     * @param   float   $weight     The weight for this group
     */
    public function setWeight($weight)
    {
        $this->weight = floatval($weight);
    }
}
