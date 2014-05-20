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
use Icinga\Application\Benchmark;
use Icinga\Web\Hook;
use Icinga\Web\Widget\Tabs;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Object\AbstractObject;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;

/**
 * Class Monitoring_ShowController
 *
 * Actions for show context
 */
class Monitoring_ShowController extends Controller
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
        } elseif ($this->getRequest()->getActionName() === 'service') {
            $this->view->object = new Service($this->getRequest());
        } else {
            // TODO: Well... this could be done better
            $this->view->object = AbstractObject::fromRequest($this->getRequest());
        }
        if (Hook::has('ticket')) {
            $this->view->tickets = Hook::first('ticket');
        }

        $this->createTabs();
    }

    /**
     * Service overview
     */
    public function serviceAction()
    {
        $this->setAutorefreshInterval(10);
        $this->view->title = $this->view->object->service_description
            . ' on ' . $this->view->object->host_name;
        $this->getTabs()->activate('service');
        $this->view->object->populate();
    }

    /**
     * Host overview
     */
    public function hostAction()
    {
        $this->setAutorefreshInterval(10);
        $this->getTabs()->activate('host');
        $this->view->title = $this->view->object->host_name;
        $this->view->object->populate();
    }

    public function historyAction()
    {
        $this->getTabs()->activate('history');
        //$this->view->object->populate();
        $this->view->object->fetchEventHistory();
        $this->handleFormatRequest($this->view->object->eventhistory);
        $this->view->history = $this->view->object->eventhistory->limit(20)->paginate();
    }

    public function servicesAction()
    {
        $this->getTabs()->activate('services');
        $this->_setParam('service', '');
        // WTF???? UrlViewFilter is messing with $_SERVER['QUERY_STRING'], great!
        $_SERVER['QUERY_STRING'] = preg_replace('~&service=[^&]+(?:&|$)~', '', $_SERVER['QUERY_STRING']);
        $this->view->services = $this->view->action('services', 'list', 'monitoring', array(
            'view'  => 'compact',
            'limit' => '',
            'sort'  => 'service_description',
            'service' => ''
        ));
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
        if (($object = $this->view->object) === null) {
            return;
        }

        $tabs = $this->getTabs();
        $params = array(
            'host' => $object->host_name,
        );
        if ($object instanceof Service) {
            $params['service'] = $object->service_description;
        } elseif ($service = $this->_getParam('service')) {
            $params['service'] = $service;
        }
        $tabs->add(
            'host',
            array(
                'title'     => 'Host',
                'icon'      => 'img/icons/host.png',
                'url'       => 'monitoring/show/host',
                'urlParams' => $params,
            )
        );
        if (isset($params['service'])) {
            $tabs->add(
                'service',
                array(
                    'title'     => 'Service',
                    'icon'      => 'img/icons/service.png',
                    'url'       => 'monitoring/show/service',
                    'urlParams' => $params,
                )
            );
        }
        $tabs->add(
            'services',
            array(
                'title'     => 'Services',
                'icon'      => 'img/icons/service.png',
                'url'       => 'monitoring/show/services',
                'urlParams' => $params,
            )
        );
        $tabs->add(
            'history',
            array(
                'title'     => 'History',
                'icon'      => 'img/icons/history.png',
                'url'       => 'monitoring/show/history',
                'urlParams' => $params,
            )
        );
        $tabs->extend(new OutputFormat())
            ->extend(new DashboardAction());
    }
}
// @codingStandardsIgnoreEnd
