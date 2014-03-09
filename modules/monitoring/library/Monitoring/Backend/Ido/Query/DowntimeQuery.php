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
 * Handling downtime queries
 */
class DowntimeQuery extends IdoQuery
{
    /**
     * Column map
     * @var array
     */
    protected $columnMap = array(
        'downtime' => array(
            'downtime_author'          => 'sd.author_name',
            'downtime_comment'         => 'sd.comment_data',
            'downtime_entry_time'      => 'UNIX_TIMESTAMP(sd.entry_time)',
            'downtime_is_fixed'        => 'sd.is_fixed',
            'downtime_is_flexible'     => 'CASE WHEN sd.is_fixed = 0 THEN 1 ELSE 0 END',
            'downtime_triggered_by_id' => 'sd.triggered_by_id',
            'downtime_scheduled_start' => 'UNIX_TIMESTAMP(sd.scheduled_start_time)',
            'downtime_scheduled_end'   => 'UNIX_TIMESTAMP(sd.scheduled_end_time)',
            'downtime_start'           => "UNIX_TIMESTAMP(CASE WHEN sd.trigger_time != '0000-00-00 00:00:00' then sd.trigger_time ELSE sd.scheduled_start_time END)",
            'downtime_end'             => 'UNIX_TIMESTAMP(sd.scheduled_end_time)',
            'downtime_duration'        => 'sd.duration',
            'downtime_is_in_effect'    => 'sd.is_in_effect',
            'downtime_internal_id'     => 'sd.internal_downtime_id',
            'downtime_host'            => 'CASE WHEN ho.name1 IS NULL THEN so.name1 ELSE ho.name1 END COLLATE latin1_general_ci',
            'downtime_service'         => 'so.name2 COLLATE latin1_general_ci',
            'downtime_objecttype'      => "CASE WHEN ho.object_id IS NOT NULL THEN 'host' ELSE CASE WHEN so.object_id IS NOT NULL THEN 'service' ELSE NULL END END",
        ),
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

        $this->baseQuery->joinLeft(
            array('ho' => $this->prefix . 'objects'),
            'sd.object_id = ho.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );

        $this->baseQuery->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'sd.object_id = so.object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );

        $this->joinedVirtualTables = array('downtime' => true);
    }
}
