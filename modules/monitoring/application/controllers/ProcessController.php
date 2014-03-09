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
use Icinga\Module\Monitoring\Backend;

use Icinga\Module\Monitoring\DataView\Runtimevariables as RuntimevariablesView;
use Icinga\Module\Monitoring\DataView\Programstatus as ProgramstatusView;
use Icinga\Module\Monitoring\DataView\Runtimesummary as RuntimesummaryView;

/**
 * Display process information and global commands
 */
class Monitoring_ProcessController extends MonitoringController
{
    /**
     * @var \Icinga\Module\Monitoring\Backend
     */
    public $backend;
    /**
     * Retrieve backend and hooks for this controller
     *
     * @see ActionController::init
     */
    public function init()
    {
        $this->backend = Backend::createBackend($this->_getParam('backend'));
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

        $this->view->programstatus = ProgramstatusView::fromRequest(
            $this->_request
        )->getQuery()->fetchRow();

        $this->view->backendName = $this->backend->getDefaultBackendName();
    }

    public function performanceAction()
    {
        $this->getTabs()->activate('performance');
        $this->setAutorefreshInterval(10);
        $this->view->runtimevariables = (object) RuntimevariablesView::fromRequest(
            $this->_request,
            array('varname', 'varvalue')
        )->getQuery()->fetchPairs();

        $this->view->checkperformance = $query = RuntimesummaryView::fromRequest(
            $this->_request
        )->getQuery()->fetchAll();
    }
}

// @codingStandardsIgnoreStop
