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
 *
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Object;

use Icinga\Data\AbstractQuery as Query;

/**
 * Represent a host object
 */
class Host extends AbstractObject
{
    protected $foreign = array(
        'hostgroups'    => null,
        'contacts'      => null,
        'contactgroups' => null,
        'customvars'    => null,
        'comments'      => null,
        'downtimes'     => null,
        'customvars'    => null
    );

    /**
     * Statename
     */
    public function stateName()
    {
        // TODO
    }

    /**
     * Filter object belongings
     *
     * @param   Query $query
     *
     * @return  Query
     */
    protected function applyObjectFilter(Query $query)
    {
        return $query->where('host_name', $this->name1);
    }

    /**
     * Load foreign object data
     *
     * @return self
     */
    public function prefetch()
    {
        return $this->fetchHostgroups()
            ->fetchContacts()
            ->fetchContactgroups()
            ->fetchCustomvars()
            ->fetchComments()
            ->fetchDowtimes()
            ->fetchCustomvars();
    }

    /**
     * Load object data
     * @return object
     */
    protected function fetchObject()
    {
        return $this->backend->select()->from(
            'status',
            array(
                'host_name',
                'host_alias',
                'host_address',
                'host_state',
                'host_handled',
                'host_in_downtime',
                'in_downtime'                   => 'host_in_downtime',
                'host_acknowledged',
                'host_last_state_change',
                'last_state_change'             => 'host_last_state_change',
                'last_notification'             => 'host_last_notification',
                'last_check'                    => 'host_last_check',
                'next_check'                    => 'host_next_check',
                'check_execution_time'          => 'host_check_execution_time',
                'check_latency'                 => 'host_check_latency',
                'output'                        => 'host_output',
                'long_output'                   => 'host_long_output',
                'check_command'                 => 'host_check_command',
                'perfdata'                      => 'host_perfdata',
                'host_icon_image',
                'passive_checks_enabled'        => 'host_passive_checks_enabled',
                'obsessing'                     => 'host_obsessing',
                'notifications_enabled'         => 'host_notifications_enabled',
                'event_handler_enabled'         => 'host_event_handler_enabled',
                'flap_detection_enabled'        => 'host_flap_detection_enabled',
                'active_checks_enabled'         => 'host_active_checks_enabled',
                'current_check_attempt'         => 'host_current_check_attempt',
                'max_check_attempts'            => 'host_max_check_attempts',
                'last_notification'             => 'host_last_notification',
                'current_notification_number'   => 'host_current_notification_number',
                'percent_state_change'          => 'host_percent_state_change',
                'is_flapping'                   => 'host_is_flapping',
                'last_comment'                  => 'host_last_comment',
                'action_url'                    => 'host_action_url',
                'notes_url'                     => 'host_notes_url',
                'percent_state_change'          => 'host_percent_state_change'
            )
        )->where('host_name', $this->name1)->fetchRow();
    }
}
