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

// @codingStandardsIgnoreStart
use \Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Controller as MonitoringController;
use \Icinga\Web\Hook;
use \Icinga\Module\Monitoring\Object\Host;
use \Icinga\Module\Monitoring\Object\Service;
use \Icinga\Application\Benchmark;
use \Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Module\Monitoring\Object\AbstractObject;
use \Icinga\Web\Widget\Tabs;

/**
 * Class Monitoring_ShowController
 *
 * Actions for show context
 */
class Monitoring_ShowController extends MonitoringController
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
        if ($this->getRequest()->getActionName() === 'host') {
            $this->view->object = new Host($this->getRequest());
        } elseif ($this->getRequest()->getActionName() === 'service'
            || $this->getRequest()->getActionName() === 'services' ) {
            $this->view->object = new Service($this->getRequest());
        } else {
            $this->view->object = AbstractObject::fromRequest($this->getRequest());
        }

        $this->createTabs();
    }

    /**
     * Service overview
     */
    public function serviceAction()
    {
        $this->view->object->populate();
    }

    /**
     * Host overview
     */
    public function hostAction()
    {
        $this->view->object->populate();
    }

    public function historyAction()
    {
        $this->view->object->populate();
        $this->view->object->fetchEventHistory();
        $this->view->history = $this->view->object->eventhistory->limit(10)->paginate();

    }

    public function servicesAction()
    {
        $params = $this->_request->getParams();
        unset($params['service']);
        $this->view->services = $this->fetchServices($params)->paginate();
    }


    /**
     * History entries for objects
     */
/*    public function historyAction()
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
    }*/

    /**
     * Creating tabs for this controller
     * @return Tabs
     */
    protected function createTabs()
    {
        $object = $this->view->object;
        $tabs = $this->getTabs();
        $params = array(
            'host' => $object->host_name,
        );
        if ($object instanceof Service) {
            $params['service'] = $object->service_description;
        } elseif ($service = $this->_getParam('service')) {
            $params['service'] = $service;
        }
        if (isset($params['service'])) {
            $tabs->add(
                'service',
                array(
                    'title'     => 'Service',
                    'iconCls'      => 'icinga-icon-service',
                    'url'       => 'monitoring/show/service',
                    'urlParams' => $params,
                    'tagParams' => array(
                        'data-icinga-target' => 'detail'
                    )
                )
            );
        }
        $tabs->add(
            'host',
            array(
                'title'     => 'Host',
                'iconCls'      => 'icinga-icon-host',
                'url'       => 'monitoring/show/host',
                'urlParams' => $params,
                'tagParams' => array(
                    'data-icinga-target' => 'detail'
                )
            )
        );
        $tabs->add(
            'services',
            array(
                'title'     => 'Services',
                'iconCls'      => 'icinga-icon-service',
                'url'       => 'monitoring/show/services',
                'urlParams' => $params,
                'tagParams' => array(
                    'data-icinga-target' => 'detail'
                )
            )
        );
        $tabs->add(
            'history',
            array(
                'title'     => 'History',
                'iconCls'      => 'icinga-icon-history',
                'url'       => 'monitoring/show/history',
                'urlParams' => $params,
                'tagParams' => array(
                    'data-icinga-target' => 'detail'
                )
            )
        );
        $tabs->extend(new OutputFormat())
             ->extend(new DashboardAction());
    }
}
// @codingStandardsIgnoreEnd
