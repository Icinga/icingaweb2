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

use Icinga\Module\Monitoring\Controller as MonitoringController;

/**
 * Display process information and global commands
 */
class Monitoring_ProcessController extends MonitoringController
{
    /**
     * Retrieve backend and hooks for this controller
     *
     * @see ActionController::init
     */
    public function init()
    {
        $this->getTabs()->add('info', array(
            'title' => 'Process Info',
            'url' =>'monitoring/process/info'
        ))->add('performance', array(
            'title' => 'Performance Info',
            'url' =>'monitoring/process/performance'
        ));
    }

    public function infoAction()
    {
        $this->getTabs()->activate('info');
        $this->setAutorefreshInterval(10);

        // TODO: This one is broken right now, doublecheck default columns
        $this->view->programstatus = $this->backend->select()
            ->from('programstatus', array(
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
                'global_service_event_handler'
            ))
            ->getQuery()->fetchRow();

        $this->view->backendName = $this->backend->getName();
    }

    public function performanceAction()
    {
        $this->getTabs()->activate('performance');
        $this->setAutorefreshInterval(10);
        $this->view->runtimevariables = (object) $this->backend->select()
            ->from('runtimevariables', array('varname', 'varvalue'))
            ->getQuery()->fetchPairs();

        $this->view->checkperformance = $this->backend->select()
            ->from('runtimesummary')
            ->getQuery()->fetchAll();
    }
}

// @codingStandardsIgnoreStop
