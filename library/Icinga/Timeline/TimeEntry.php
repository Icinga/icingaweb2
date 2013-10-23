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
use \Icinga\Exception\ProgrammingError;

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
     * The color of this group
     *
     * @var string
     */
    private $color;

    /**
     * Return a new TimeEntry object with the given attributes being set
     *
     * @param   array       $attributes     The attributes to set
     * @return  TimeEntry                   The resulting TimeEntry object
     * @throws  ProgrammingError            If one of the given attributes cannot be set
     */
    public static function fromArray(array $attributes)
    {
        $entry = new TimeEntry();

        foreach ($attributes as $name => $value) {
            $methodName = 'set' . ucfirst($name);
            if (method_exists($entry, $methodName)) {
                $entry->{$methodName}($value);
            } else {
                throw new ProgrammingError(
                    'Method "' . $methodName . '" does not exist on object of type "' . __CLASS__ . '"'
                );
            }
        }

        return $entry;
    }

    /**
     * Set this group's name
     *
     * @param   string  $name   The name to set
     */
    public function setName($name)
    {
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
     * Set this group's amount of events
     *
     * @param   int     $value  The value to set
     */
    public function setValue($value)
    {
        $this->value = intval($value);
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
     * Set this group's date and time
     *
     * @param   DateTime    $dateTime   The date and time to set
     */
    public function setDateTime(DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
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
     * Set the url to this group's detail view
     *
     * @param   string  $detailUrl  The url to set
     */
    public function setDetailUrl($detailUrl)
    {
        $this->detailUrl = $detailUrl;
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
     * Set this group's weight
     *
     * @param   float   $weight     The weight for this group
     */
    public function setWeight($weight)
    {
        $this->weight = floatval($weight);
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
     * Set this group's color
     *
     * @param   string  $color  The color to set. (The css name or hex-code)
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * Get the color of this group
     *
     * @return  string
     */
    public function getColor()
    {
        return $this->color;
    }
}
