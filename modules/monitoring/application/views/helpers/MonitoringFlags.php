<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

/*use Icinga\Module\Monitoring\Object\AbstractObject;*/

/**
 * Rendering helper for object's properties which may be either enabled or disabled
 */
class Zend_View_Helper_MonitoringFlags extends Zend_View_Helper_Abstract
{
    /**
     * Object's properties which may be either enabled or disabled and their human readable description
     *
     * @var string[]
     */
    private static $flags = array(
        'passive_checks_enabled'    => 'Passive Checks',
        'active_checks_enabled'     => 'Active Checks',
        'obsessing'                 => 'Obsessing',
        'notifications_enabled'     => 'Notifications',
        'event_handler_enabled'     => 'Event Handler',
        'flap_detection_enabled'    => 'Flap Detection',
    );

    /**
     * Retrieve flags as array with either true or false as value
     *
     * @param   AbstractObject $object
     *
     * @return  array
     */
    public function monitoringFlags(/*AbstractObject*/$object)
    {
        $flags = array();
        foreach (self::$flags as $column => $description) {
            $flags[$description] = (bool) $object->{$column};
        }
        return $flags;
    }
}
