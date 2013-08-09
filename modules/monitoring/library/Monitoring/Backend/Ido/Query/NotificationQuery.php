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

namespace Monitoring\Backend\Ido\Query;


/**
 * Handling notification queries
 */
class NotificationQuery extends AbstractQuery
{
    /**
     * Column map
     *
     * @var array
     */
    protected $columnMap = array(
        'notification' => array(
            'notification_type' => '',
            'notification_start_time' => '',
            'notification_information' => ''
        ),
        'objects' => array(
            'host_name' => '',
            'service_description' => ''
        ),
        'contact' => array(
            'notification_contact' => ''
        ),
        'timeperiod' => array(
            'notification_timeperiod' => ''
        )
    );

    /**
     * Fetch basic information about notifications
     */
    protected function joinBaseTables()
    {
        
    }

    /**
     * Fetch description of each affected host/service
     */
    protected function joinObjects()
    {
        
    }

    /**
     * Fetch name of involved contacts and/or contact groups
     */
    protected function joinContact()
    {
        
    }

    /**
     * Fetch assigned time period for each notification
     */
    protected function joinTimeperiod()
    {
        
    }
}