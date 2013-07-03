<?php
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

use Icinga\Monitoring\Command\Meta;

/**
 * Class MonitoringCommands
 */
class Zend_View_Helper_MonitoringCommands extends Zend_View_Helper_Abstract
{
    /**
     * Type of small interface style
     */
    const TYPE_SMALL = 'small';

    /**
     * Type of full featured interface style
     */
    const TYPE_FULL = 'full';
    /**
     * Returns the object type from object
     * @param stdClass $object
     * @return string
     */
    public function getObjectType(\stdClass $object)
    {
        return array_shift(explode('_', array_shift(array_keys(get_object_vars($object))), 2));
    }

    public function monitoringCommands(\stdClass $object, $type)
    {
        $type = $this->getObjectType($object);

        $commands = new Meta();
        var_dump($commands->getCommandsForObject($object));


        var_dump($type);
    }
}