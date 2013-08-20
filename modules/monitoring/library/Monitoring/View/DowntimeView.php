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
namespace Icinga\Module\Monitoring\View;

/**
 * Class DowntimeView
 */
class DowntimeView extends MonitoringView
{
    /**
     * Query object
     * @var mixed
     */
    protected $query;

    /**
     * Available columns
     * @var string[]
     */
    protected $availableColumns = array(
        'host_name',
        'object_type',
        'service_host_name',
        'service_description',
        'downtime_type',
        'downtime_author_name',
        'downtime_comment_data',
        'downtime_is_fixed',
        'downtime_duration',
        'downtime_entry_time',
        'downtime_scheduled_start_time',
        'downtime_scheduled_end_time',
        'downtime_was_started',
        'downtime_actual_start_time',
        'downtime_actual_start_time_usec',
        'downtime_is_in_effect',
        'downtime_trigger_time',
        'downtime_triggered_by_id',
        'downtime_internal_downtime_id'
    );

    /**
     * Filters
     * @var array
     */
    protected $specialFilters = array();

    /**
     * Default sorting of data set
     * @var array
     */
    protected $sortDefaults = array(
        'downtime_is_in_effect' => array(
            'default_dir' => self::SORT_DESC
        ),
        'downtime_actual_start_time' => array(
            'default_dir' => self::SORT_DESC
        )
    );
}
