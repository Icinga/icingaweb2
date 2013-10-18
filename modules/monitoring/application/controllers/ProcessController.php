<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 *
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
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
    }

    public function performanceAction()
    {
        $this->view->runtimevariables = (object)RuntimevariablesView::fromRequest(
            $this->_request,
            array('varname', 'varvalue')
        )->getQuery()->fetchPairs();

        $this->view->programstatus = ProgramstatusView::fromRequest(
            $this->_request
        )->getQuery()->fetchRow();

        $this->view->checkperformance = $query = RuntimesummaryView::fromRequest(
            $this->_request
        )->getQuery()->fetchAll();



        $this->view->backendName = $this->backend->getDefaultBackendName();
    }
}

// @codingStandardsIgnoreStop