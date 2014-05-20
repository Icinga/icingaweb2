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

namespace Icinga\Module\Monitoring\DataView;

use Icinga\Module\Monitoring\Filter\MonitoringFilter;

class HostStatus extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'host',
            'host_name',
            'host_alias',
            'host_address',
            'host_state',
            'host_state_type',
            'host_handled',
            'host_unhandled',
            'host_in_downtime',
            'host_acknowledged',
            'host_last_state_change',
            'host_last_state_change',
            'host_last_notification',
            'host_last_check',
            'host_next_check',
            'host_check_execution_time',
            'host_check_latency',
            'host_output',
            'host_long_output',
            'host_check_command',
            'host_perfdata',
	    'host_check_source',
            'host_passive_checks_enabled',
            'host_passive_checks_enabled_changed',
            'host_obsessing',
            'host_obsessing_changed',
            'host_notifications_enabled',
            'host_notifications_enabled_changed',
            'host_event_handler_enabled',
            'host_event_handler_enabled_changed',
            'host_flap_detection_enabled',
            'host_flap_detection_enabled_changed',
            'host_active_checks_enabled',
            'host_active_checks_enabled_changed',
            'host_current_check_attempt',
            'host_max_check_attempts',
            'host_last_notification',
            'host_current_notification_number',
            'host_percent_state_change',
            'host_is_flapping',
            'host_last_comment',
            'host_action_url',
            'host_notes_url',
            'host_percent_state_change',
            'host_modified_host_attributes',
            'host_severity',
            'host_problem'
        );
    }

    /**
     * Return the table name
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'status';
    }

    /**
     * The sort rules for this query
     *
     * @return array
     */
    public function getSortRules()
    {
        return array(
            'host_name' => array(
                'order' => self::SORT_ASC
            ),
            'host_address' => array(
                'columns' => array(
                    'host_ipv4'
                ),
                'order' => self::SORT_ASC
            ),
            'host_last_state_change' => array(
                'order' => self::SORT_ASC
            ),
            'host_severity' => array(
                'columns' => array(
                    'host_severity',
                    'host_last_state_change',
                ),
                'order' => self::SORT_DESC
            )
        );
    }

    public function getFilterColumns()
    {
        return array('hostgroup', 'service_problems');
    }

    public function isValidFilterTarget($column)
    {
        if ($column[0] === '_'
            && preg_match('/^_(?:host|service)_/', $column)
        ) {
            return true;
        }
        return parent::isValidFilterTarget($column);
    }
}
