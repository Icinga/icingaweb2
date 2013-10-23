<?php
// @codingStandardsIgnoreStart
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

use \Zend_Db_Select;

class StatusSummaryQuery extends IdoQuery
{
    protected $columnMap = array(
        'hoststatussummary'     => array(
            'hosts_up'                      => 'SUM(CASE WHEN object_type = \'host\' AND state = 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable_handled'     => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND acknowledged + in_downtime != 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable_unhandled'   => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'hosts_down_handled'            => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND acknowledged + in_downtime != 0 THEN 1 ELSE 0 END)',
            'hosts_down_unhandled'          => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'hosts_pending'                 => 'SUM(CASE WHEN object_type = \'host\' AND state = 99 THEN 1 ELSE 0 END)'
        ),
        'servicestatussummary'  => array(
            'services_ok'                   => 'SUM(CASE WHEN object_type = \'service\' AND state = 0 THEN 1 ELSE 0 END)',
            'services_pending'              => 'SUM(CASE WHEN object_type = \'service\' AND state = 99 THEN 1 ELSE 0 END)',
            'services_warning_handled'      => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) > 0 THEN 1 ELSE 0 END)',
            'services_critical_handled'     => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) > 0 THEN 1 ELSE 0 END)',
            'services_unknown_handled'      => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) > 0 THEN 1 ELSE 0 END)',
            'services_warning_unhandled'    => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) = 0 THEN 1 ELSE 0 END)',
            'services_critical_unhandled'   => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) = 0 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled'    => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) = 0 THEN 1 ELSE 0 END)'
        )
    );

    protected function joinBaseTables()
    {
        $hosts = $this->db->select()->from(
            array('ho' => $this->prefix . 'objects'),
            array()
        )->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'ho.object_id = hs.host_object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array(
                ''
            )
        )->join(
            array('h' => $this->prefix . 'hosts'),
            'hs.host_object_id = h.host_object_id',
            array()
        );
        $services = clone $hosts;
        $services->join(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = h.host_object_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.' . $this->object_id . ' = s.service_object_id AND so.is_active = 1',
            array()
        )->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            'so.' . $this->object_id . ' = ss.service_object_id',
            array()
        );
        $hosts->columns(array(
            'state'         => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'acknowledged'  => 'hs.problem_has_been_acknowledged',
            'in_downtime'   => 'CASE WHEN (hs.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
            'host_state'    => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'object_type'   => '(\'host\')'
        ));
        $services->columns(array(
            'state'         => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END',
            'acknowledged'  => 'ss.problem_has_been_acknowledged',
            'in_downtime'   => 'CASE WHEN (ss.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
            'host_state'    => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'object_type'   => '(\'service\')'
        ));
        $union = $this->db->select()->union(array($hosts, $services), Zend_Db_Select::SQL_UNION_ALL);
        $this->baseQuery = $this->db->select()->from(array('statussummary' => $union), array());
        $this->joinedVirtualTables = array(
            'servicestatussummary'  => true,
            'hoststatussummary'     => true
        );
    }
}
// @codingStandardsIgnoreStop
