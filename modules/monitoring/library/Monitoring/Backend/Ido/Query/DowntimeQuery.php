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

namespace Icinga\Module\Monitoring\Backend\Ido\Query;


/**
 * Handling downtime queries
 */
class DowntimeQuery extends AbstractQuery
{
    /**
     * Column map
     * @var array
     */
    protected $columnMap = array(
        'downtime' => array(
            'downtime_type' => 'sd.downtime_type',
            'downtime_author_name' => 'sd.author_name',
            'downtime_comment_data' => 'sd.comment_data',
            'downtime_entry_time' => 'sd.entry_time',
            'downtime_is_fixed' => 'sd.is_fixed',
            'downtime_duration' => 'sd.duration',
            'downtime_scheduled_start_time' => 'sd.scheduled_start_time',
            'downtime_scheduled_end_time' => 'sd.scheduled_end_time',
            'downtime_was_started' => 'sd.was_started',
            'downtime_actual_start_time' => 'sd.actual_start_time',
            'downtime_actual_start_time_usec' => 'sd.actual_start_time_usec',
            'downtime_is_in_effect' => 'sd.is_in_effect',
            'downtime_trigger_time' => 'sd.trigger_time',
            'downtime_triggered_by_id' => 'sd.triggered_by_id',
            'downtime_internal_downtime_id' => 'sd.internal_downtime_id'
        ),
        'objects' => array(
            'host_name' => 'o.name1 COLLATE latin1_general_ci',
            'service_host_name' => 'o.name1 COLLATE latin1_general_ci',
            'service_description' => 'o.name2 COLLATE latin1_general_ci',
            'object_type' => "CASE o.objecttype_id WHEN 1 THEN 'host' ELSE 'service' END",
        )
    );

    /**
     * Join with scheduleddowntime
     */
    protected function joinBaseTables()
    {
        $this->baseQuery = $this->db->select()->from(
            array('sd' => $this->prefix . 'scheduleddowntime'),
            array()
        );

        $this->joinedVirtualTables = array('downtime' => true, 'services' => true);
    }

    /**
     * Join if host needed
     */
    protected function joinObjects()
    {
        $this->baseQuery->join(
            array('o' => $this->prefix . 'objects'),
            'sd.object_id = o.object_id AND o.is_active = 1 AND o.objecttype_id IN (1, 2)',
            array()
        );
    }
}
