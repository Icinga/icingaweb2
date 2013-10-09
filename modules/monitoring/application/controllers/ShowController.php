<?php
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

// @codingStandardsIgnoreStart
use \Icinga\Module\Monitoring\Backend;
use \Icinga\Web\Controller\ActionController;
use \Icinga\Web\Hook;
use \Icinga\Module\Monitoring\Object\Host;
use \Icinga\Module\Monitoring\Object\Service;
use \Icinga\Application\Benchmark;
use \Icinga\Web\Widget\Tabextension\OutputFormat;
use \Icinga\Web\Widget\Tabextension\DashboardAction;
use \Icinga\Web\Widget\Tabextension\BasketAction;
use \Icinga\Web\Widget\Tabs;

/**
 * Class Monitoring_ShowController
 *
 * Actions for show context
 */
class Monitoring_ShowController extends ActionController
{
    /**
     * @var Backend
     */
    protected $backend;

    /**
     * Initialize the controller
     */
    public function init()
    {
        $host = $this->_getParam('host');
        $service = $this->_getParam('service');
        $this->backend = Backend::createBackend($this->_getParam('backend'));
        $object = null;
        // TODO: Do not allow wildcards in names!
        if ($host !== null) {
            // TODO: $this->assertPermission('host/read', $host);
            if ($this->getRequest()->getActionName() !== 'host' && $service !== null && $service !== '*') {
                // TODO: $this->assertPermission('service/read', $service);
                $object = Service::fetch($this->backend, $host, $service);
            } else {
                $object = Host::fetch($this->backend, $host);
            }
        }

        $this->view->compact = $this->_getParam('view') === 'compact';
        if ($object === null) {
            // TODO: Notification, not found
            $this->redirectNow('monitoring/list/services');
            return;
        }
        $this->view->object = $object;
        $this->createTabs();
    }

    /**
     * Service overview
     */
    public function serviceAction()
    {
        $this->view->object->prefetch();
        $this->view->object->eventHistory = $this->view->object->eventHistory->limit(10)->fetchAll();
        $this->view->preserve = array();
    }

    /**
     * Host overview
     */
    public function hostAction()
    {
        $this->view->object->prefetch();
        $this->view->object->eventHistory = $this->view->object->eventHistory->limit(10)->fetchAll();
        $this->view->preserve = array();
    }

    /**
     * History entries for objects
     */
    public function historyAction()
    {
        $this->view->history = $this->backend->select()
            ->from(
                'eventHistory',
                array(
                    'object_type',
                    'host_name',
                    'service_description',
                    'timestamp',
                    'state',
                    'attempt',
                    'max_attempts',
                    'output',
                    'type'
                )
            )->applyRequest($this->_request);

        $this->view->preserve = $this->view->history->getAppliedFilter()->toParams();
        if ($this->_getParam('dump') === 'sql') {
            echo '<pre>' . htmlspecialchars($this->view->history->getQuery()->dump()) . '</pre>';
            exit;
        }
        if ($this->_getParam('sort')) {
            $this->view->preserve['sort'] = $this->_getParam('sort');
        }
        $this->view->preserve = $this->view->history->getAppliedFilter()->toParams();
    }

    /**
     * Creating tabs for this controller
     * @return Tabs
     */
    protected function createTabs()
    {
        $object = $this->view->object;
        $tabs = $this->getTabs();
        if (!$this->view->host) {
            return $tabs;
        }
        $params = array(
            'host' => $this->view->host->host_name,
        );
        if ($backend = $this->_getParam('backend')) {
            $params['backend'] = $backend;
        }
        if ($object instanceof Service) {
            $params['service'] = $object->service_description;
        } elseif ($service = $this->_getParam('service')) {
            $params['service'] = $service;
        }

        $tabs->extend(new OutputFormat())
            ->extend(new DashboardAction())
            ->extend(new BasketAction);

        return $tabs;
    }
}
// @codingStandardsIgnoreEnd
