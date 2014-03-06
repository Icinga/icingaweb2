<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Notification query
 */
class NotificationQuery extends IdoQuery
{
    /**
     * Column map
     *
     * @var array
     */
    protected $columnMap = array(
        'notification' => array(
            'notification_output'       => 'n.output',
            'notification_start_time'   => 'UNIX_TIMESTAMP(n.start_time)',
            'notification_state'        => 'n.state'
        ),
        'objects' => array(
            'host'                      => 'o.name1',
            'service'                   => 'o.name2'
        ),
        'contact' => array(
            'notification_contact'      => 'c_o.name1'
        ),
        'command' => array(
            'notification_command'      => 'cmd_o.name1'
        )
    );

    /**
     * Fetch basic information about notifications
     */
    protected function joinBaseTables()
    {
        $this->baseQuery = $this->db->select()->from(
            array(
                'n' => $this->prefix . 'notifications'
            ),
            array()
        );
        $this->joinedVirtualTables = array('notification' => true);
    }

    /**
     * Fetch description of each affected host/service
     */
    protected function joinObjects()
    {
        $this->baseQuery->join(
            array(
                'o' => $this->prefix . 'objects'
            ),
            'n.object_id = o.object_id AND o.is_active = 1 AND o.objecttype_id IN (1, 2)',
            array()
        );
    }

    /**
     * Fetch name of involved contacts and/or contact groups
     */
    protected function joinContact()
    {
        $this->baseQuery->join(
            array(
                'c' => $this->prefix . 'contactnotifications'
            ),
            'n.notification_id = c.notification_id',
            array()
        );
        $this->baseQuery->join(
            array(
                'c_o' => $this->prefix . 'objects'
            ),
            'c.contact_object_id = c_o.object_id',
            array()
        );
    }

    /**
     * Fetch name of the command which was used to send out a notification
     */
    protected function joinCommand()
    {
        $this->baseQuery->join(
            array(
                'cmd_c' => $this->prefix . 'contactnotifications'
            ),
            'n.notification_id = cmd_c.notification_id',
            array()
        );
        $this->baseQuery->join(
            array(
                'cmd_m' => $this->prefix . 'contactnotificationmethods'
            ),
            'cmd_c.contactnotification_id = cmd_m.contactnotification_id',
            array()
        );
        $this->baseQuery->joinLeft(
            array(
                'cmd_o' => $this->prefix . 'objects'
            ),
            'cmd_m.command_object_id = cmd_o.object_id',
            array()
        );
    }
}
