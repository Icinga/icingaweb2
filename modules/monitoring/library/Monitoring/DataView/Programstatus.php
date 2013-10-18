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

namespace Icinga\Module\Monitoring\DataView;

/**
 * View for programstatus query
 */
class Programstatus extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'id',
            'status_update_time',
            'program_start_time',
            'program_end_time',
            'is_currently_running',
            'process_id',
            'daemon_mode',
            'last_command_check',
            'last_log_rotation',
            'notifications_enabled',
            'disable_notif_expire_time',
            'active_service_checks_enabled',
            'passive_service_checks_enabled',
            'active_host_checks_enabled',
            'passive_host_checks_enabled',
            'event_handlers_enabled',
            'flap_detection_enabled',
            'failure_prediction_enabled',
            'process_performance_data',
            'obsess_over_hosts',
            'obsess_over_services',
            'modified_host_attributes',
            'modified_service_attributes',
            'global_host_event_handler',
            'global_service_event_handler',
        );
    }

    /**
     * Retrieve default sorting rules for particular columns. These involve sort order and potential additional to sort
     *
     * @return array
     */
    public function getSortRules()
    {
        return array(
            'id' => array(
                'order' => self::SORT_DESC
            )
        );
    }
}
