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

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

/**
 * Handling downtime queries
 */
class DowntimeQuery extends StatusdatQuery
{
    /**
     * Column map
     * @var array
     */
    public static $mappedParameters = array(
        'downtime_author'                   => 'author',
        'downtime_comment'                  => 'comment',
        'downtime_duration'                 => 'duration',
        'downtime_end'                      => 'end_time',
        'downtime_was_started'              => 'was_started',
        'downtime_is_fixed'                 => 'fixed',
        'downtime_is_in_effect'             => 'is_in_effect',
        'downtime_trigger_time'             => 'trigger_time',
        'downtime_triggered_by_id'          => 'triggered_by_id',
        'downtime_internal_downtime_id'     => 'internal_downtime_id',
        'host'                              => 'host_name',
        'host_name'                         => 'host_name',
        'service_host_name'                 => 'host_name',
        'service_description'               => 'service_description',
    );

    public static $handlerParameters = array(
        'object_type'                       => 'getObjectType',
        'downtime_start'                    => 'getDowntimeStart',
        'downtime_is_flexible'              => 'getFlexibleFlag'
    );

    public static $fieldTypes = array(
        'downtime_end'          => self::TIMESTAMP,
        'downtime_trigger_time' => self::TIMESTAMP,
        'downtime_start'        => self::TIMESTAMP
    );


    public function getDowntimeStart(&$obj)
    {
        if ($obj->trigger_time != '0') {
            return $obj->trigger_time;
        } else {
            return $obj->start_time;
        }
    }


    public function getFlexibleFlag(&$obj)
    {
        return $obj->fixed ? 0 : 1;
    }

    public function getObjectType(&$obj)
    {
        return isset($obj->service_description) ? 'service ': 'host';
    }

    public function selectBase()
    {
        $this->select()->from("downtimes", array());
    }
}
