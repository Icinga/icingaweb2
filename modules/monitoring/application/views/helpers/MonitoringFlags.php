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

use Icinga\Module\Monitoring\Object\AbstractObject;

/**
 * Class Zend_View_Helper_MonitoringFlags
 *
 * Rendering helper for flags depending on objects
 */
class Zend_View_Helper_MonitoringFlags extends Zend_View_Helper_Abstract
{
    /**
     * Key of flags without prefix (e.g. host or service)
     * @var string[]
     */
    private static $keys = array(
        'passive_checks_enabled' => 'Passive Checks',
        'active_checks_enabled' => 'Active Checks',
        'obsessing' => 'Obsessing',
        'notifications_enabled' => 'Notifications',
        'event_handler_enabled' => 'Event Handler',
        'flap_detection_enabled' => 'Flap Detection',
    );

    /**
     * Type prefix
     * @param array $vars
     * @return string
     */
    private function getObjectType(array $vars)
    {
        $keys = array_keys($vars);
        $firstKey = array_shift($keys);
        $keyParts = explode('_', $firstKey, 2);

        return array_shift($keyParts);
    }

    /**
     * Build all existing flags to a readable array
     * @param stdClass $object
     * @return array
     */
    public function monitoringFlags(AbstractObject $object)
    {
        $vars = (array)$object;
        $type = $this->getObjectType($vars);
        $out = array();

        foreach (self::$keys as $key => $name) {
            $value = false;
            if (array_key_exists(($realKey = $type. '_'. $key), $vars)) {
                $value = $vars[$realKey] === '1' ? true : false;
            }
            $out[$name] = $value;
        }

        return $out;
    }
}
