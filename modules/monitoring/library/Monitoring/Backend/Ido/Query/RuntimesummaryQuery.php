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

use \Zend_Db_Select;

/**
 * Query check summaries out of database
 */
class RuntimesummaryQuery extends IdoQuery
{
    protected $columnMap = array(
        'runtimesummary' => array(
            'check_type'                => 'check_type',
            'active_checks_enabled'     => 'active_checks_enabled',
            'passive_checks_enabled'    => 'passive_checks_enabled',
            'execution_time'            => 'execution_time',
            'latency'                   => 'latency',
            'object_count'              => 'object_count',
            'object_type'               => 'object_type'
        )
    );

    protected function joinBaseTables()
    {
        $p = $this->prefix;

        $hostColumns = array(
            'check_type'                => 'CASE '
            . 'WHEN ' . $p
            . 'hoststatus.active_checks_enabled = 0 AND '
            . $p . 'hoststatus.passive_checks_enabled = 1 '
            . 'THEN \'passive\' '
            . 'WHEN ' . $p . 'hoststatus.active_checks_enabled = 1 THEN \'active\' END',
            'active_checks_enabled'     => 'active_checks_enabled',
            'passive_checks_enabled'    => 'passive_checks_enabled',
            'execution_time'            => 'SUM(execution_time)',
            'latency'                   => 'SUM(latency)',
            'object_count'              => 'COUNT(*)',
            'object_type'               => "('host')"
        );

        $serviceColumns = array(
            'check_type'                => 'CASE '
            . 'WHEN ' . $p
            . 'servicestatus.active_checks_enabled = 0 AND ' . $p
            . 'servicestatus.passive_checks_enabled = 1 '
            . 'THEN \'passive\' '
            . 'WHEN ' . $p . 'servicestatus.active_checks_enabled = 1 THEN \'active\' END',
            'active_checks_enabled'     => 'active_checks_enabled',
            'passive_checks_enabled'    => 'passive_checks_enabled',
            'execution_time'            => 'SUM(execution_time)',
            'latency'                   => 'SUM(latency)',
            'object_count'              => 'COUNT(*)',
            'object_type'               => "('service')"
        );

        $hosts = $this->db->select()->from($this->prefix . 'hoststatus', $hostColumns)
            ->group('check_type')->group('active_checks_enabled')->group('passive_checks_enabled');

        $services = $this->db->select()->from($this->prefix . 'servicestatus', $serviceColumns)
            ->group('check_type')->group('active_checks_enabled')->group('passive_checks_enabled');

        $union = $this->db->select()->union(
            array('s' => $services, 'h' => $hosts),
            Zend_Db_Select::SQL_UNION_ALL
        );

        $this->select->from(array('hs' => $union));

        $this->joinedVirtualTables = array('runtimesummary' => true);
    }
}
