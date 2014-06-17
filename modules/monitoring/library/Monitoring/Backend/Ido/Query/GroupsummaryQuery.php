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

use Zend_Db_Select;

class GroupSummaryQuery extends IdoQuery
{
    protected $useSubqueryCount = true;

    protected $columnMap = array(
        'hoststatussummary'     => array(
            'hosts_up'                      => 'SUM(CASE WHEN object_type = \'host\' AND state = 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable'             => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 THEN 1 ELSE 0 END)',
            'hosts_unreachable_handled'     => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND acknowledged + in_downtime != 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable_unhandled'   => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'hosts_down'                    => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 THEN 1 ELSE 0 END)',
            'hosts_down_handled'            => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND acknowledged + in_downtime != 0 THEN 1 ELSE 0 END)',
            'hosts_down_unhandled'          => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'hosts_pending'                 => 'SUM(CASE WHEN object_type = \'host\' AND state = 99 THEN 1 ELSE 0 END)',
            'hostgroup'                     => 'hostgroup'
        ),
        'servicestatussummary'  => array(
            'services_ok'                   => 'SUM(CASE WHEN object_type = \'service\' AND state = 0 THEN 1 ELSE 0 END)',
            'services_pending'              => 'SUM(CASE WHEN object_type = \'service\' AND state = 99 THEN 1 ELSE 0 END)',
            'services_warning'              => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 THEN 1 ELSE 0 END)',
            'services_warning_handled'      => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) > 0 THEN 1 ELSE 0 END)',
            'services_critical'             => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 THEN 1 ELSE 0 END)',
            'services_critical_handled'     => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) > 0 THEN 1 ELSE 0 END)',
            'services_unknown'              => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 THEN 1 ELSE 0 END)',
            'services_unknown_handled'      => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) > 0 THEN 1 ELSE 0 END)',
            'services_warning_unhandled'    => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) = 0 THEN 1 ELSE 0 END)',
            'services_critical_unhandled'   => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) = 0 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled'    => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND (acknowledged + in_downtime + COALESCE(host_state, 0)) = 0 THEN 1 ELSE 0 END)',
            'servicegroup'                  => 'servicegroup'
        )
    );

    protected function joinBaseTables()
    {
        $columns = array(
            'object_type',
            'host_state',
            'host_name'
        );

        // Prepend group column since we'll use columns index 0 later for grouping
        if (in_array('servicegroup', $this->desiredColumns)) {
            array_unshift($columns, 'servicegroup');
        } else {
            array_unshift($columns, 'hostgroup');
        }
        $hosts = $this->createSubQuery(
            'Hoststatus',
            $columns + array(
                'state'        => 'host_state',
                'acknowledged' => 'host_acknowledged',
                'in_downtime'  => 'host_in_downtime'
            )
        );
        $services = $this->createSubQuery(
            'Status',
            $columns + array(
                'state'        => 'service_state',
                'acknowledged' => 'service_acknowledged',
                'in_downtime'  => 'service_in_downtime'
            )
        );

        $union = $this->db->select()->union(array($hosts, $services), Zend_Db_Select::SQL_UNION_ALL);
        $this->select->from(array('statussummary' => $union), '*')->group($columns[0]);
        $this->joinedVirtualTables = array(
            'servicestatussummary'  => true,
            'hoststatussummary'     => true
        );
    }
}
// @codingStandardsIgnoreStop

