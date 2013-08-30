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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

use Monitoring\Backend;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Hook;
use Monitoring\Object\Host;
use Monitoring\Object\Service;
use Icinga\Application\Benchmark;

use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\BasketAction;

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
        $this->backend = Backend::getInstance($this->_getParam('backend'));
        $object = null;
        // TODO: Do not allow wildcards in names!
        if ($host !== null) {
            // TODO: $this->assertPermission('host/read', $host);
            if ($this->action_name !== 'host' && $service !== null && $service !== '*') {
                // TODO: $this->assertPermission('service/read', $service);
                $object = Service::fetch($this->backend, $host, $service);
            } else {
                $object = Host::fetch($this->backend, $host);
            }
        }

        $this->backend = Backend::getInstance($this->_getParam('backend'));
        if ($service !== null && $service !== '*') {
            $this->view->service = $this->backend->fetchService($host, $service, true);
        }
        if ($host !== null) {
            $this->view->host = $this->backend->fetchHost($host, true);
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
        Benchmark::measure('Entered service action');
        $this->view->active = 'service';

        if ($grapher = Hook::get('grapher')) {
            if ($grapher->hasGraph(
                $this->view->service->host_name,
                $this->view->service->service_description
            )
            ) {
                $this->view->preview_image = $grapher->getPreviewImage(
                    $this->view->service->host_name,
                    $this->view->service->service_description
                );
            }
        }

        $this->view->servicegroups = $this->backend->select()
            ->from(
                'servicegroup',
                array(
                    'servicegroup_name',
                    'servicegroup_alias'
                )
            )
            ->where('host_name', $this->view->host->host_name)
            ->where('service_description', $this->view->service->service_description)

            ->fetchPairs();

        $this->view->contacts = $this->backend->select()
            ->from(
                'contact',
                array(
                    'contact_name',
                    'contact_alias',
                    'contact_email',
                    'contact_pager',
                )
            )
            ->where('service_host_name', $this->view->host->host_name)
            ->where('service_description', $this->view->service->service_description)
            ->fetchAll();

        $this->view->contactgroups = $this->backend->select()
            ->from(
                'contactgroup',
                array(
                    'contactgroup_name',
                    'contactgroup_alias',
                )
            )
            ->where('service_host_name', $this->view->host->host_name)
            ->where('service_description', $this->view->service->service_description)
            ->fetchAll();

        $this->view->comments = $this->backend->select()
            ->from(
                'comment',
                array(
                    'comment_timestamp',
                    'comment_author',
                    'comment_data',
                    'comment_type',
                )
            )
            ->where('service_host_name', $this->view->host->host_name)
            ->where('service_description', $this->view->service->service_description)
            ->fetchAll();

        $this->view->downtimes = $this->backend->select()
            ->from(
                'downtime',
                array(
                    'host_name',
                    'service_description',
                    'downtime_type',
                    'downtime_author_name',
                    'downtime_comment_data',
                    'downtime_is_fixed',
                    'downtime_duration',
                    'downtime_scheduled_start_time',
                    'downtime_scheduled_end_time',
                    'downtime_actual_start_time',
                    'downtime_was_started',
                    'downtime_is_in_effect',
                    'downtime_internal_downtime_id'
                )
            )
            ->where('host_name', $this->view->host->host_name)
            ->where('service_description', $this->view->service->service_description)
            ->where('object_type','service')
            ->fetchAll();

        $this->view->customvars = $this->backend->select()
            ->from(
                'customvar',
                array(
                    'varname',
                    'varvalue'
                )
            )
            ->where('varname', '-*PW*,-*PASS*')
            ->where('host_name', $this->view->host->host_name)
            ->where('service_description', $this->view->service->service_description)
            ->where('object_type', 'service')
            ->fetchPairs();
        Benchmark::measure('Service action done');
        $object = $this->view->object->prefetch();
        $this->prepareGrapherHook();
    }

    /**
     * Host overview
     */
    public function hostAction()
    {
        $this->view->active = 'host';

        if ($grapher = Hook::get('grapher')) {
            if ($grapher->hasGraph($this->view->host->host_name)) {
                $this->view->preview_image = $grapher->getPreviewImage(
                    $this->view->host->host_name
                );
            }
        }

        $this->view->hostgroups = $this->backend->select()
            ->from(
                'hostgroup',
                array(
                    'hostgroup_name',
                    'hostgroup_alias'
                )
            )
            ->where('host_name', $this->view->host->host_name)
            ->fetchPairs();

        $this->view->contacts = $this->backend->select()
            ->from(
                'contact',
                array(
                    'contact_name',
                    'contact_alias',
                    'contact_email',
                    'contact_pager',
                )
            )
            ->where('host_name', $this->view->host->host_name)
            ->fetchAll();

        $this->view->contactgroups = $this->backend->select()
            ->from(
                'contactgroup',
                array(
                    'contactgroup_name',
                    'contactgroup_alias',
                )
            )
            ->where('host_name', $this->view->host->host_name)
            ->fetchAll();

        $this->view->comments = $this->backend->select()
            ->from(
                'comment',
                array(
                    'comment_timestamp',
                    'comment_author',
                    'comment_data',
                    'comment_type',
                )
            )
            ->where('host_name', $this->view->host->host_name)
            ->fetchAll();

        $this->view->downtimes = $this->backend->select()
            ->from(
                'downtime',
                array(
                    'host_name',
                    'downtime_type',
                    'downtime_author_name',
                    'downtime_comment_data',
                    'downtime_is_fixed',
                    'downtime_duration',
                    'downtime_scheduled_start_time',
                    'downtime_scheduled_end_time',
                    'downtime_actual_start_time',
                    'downtime_was_started',
                    'downtime_is_in_effect',
                    'downtime_internal_downtime_id'
                )
            )
            ->where('host_name', $this->view->host->host_name)
            ->where('object_type','host')
            ->fetchAll();

        $this->view->customvars = $this->backend->select()
            ->from(
                'customvar',
                array(
                    'varname',
                    'varvalue'
                )
            )
            ->where('varname', '-*PW*,-*PASS*')
            ->where('host_name', $this->view->host->host_name)
            ->where('object_type', 'host')
            ->fetchPairs();
        $this->view->object->prefetch();
        $this->prepareGrapherHook();
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
     * Service overview
     */
    public function servicesAction()
    {
        $this->_setParam('service', null);
        // Ugly and slow:
        $this->view->services = $this->view->action(
            'services',
            'list',
            'monitoring',
            array(
                'host_name' => $this->view->host->host_name,
                //'sort', 'service_description'
            )
        );
        $this->view->services = $this->view->action('services', 'list', 'monitoring', array(
            'view' => 'compact'
        ));
    }

    protected function prepareGrapherHook()
    {
        if ($grapher = Hook::get('grapher')) {
            $object = $this->view->object;
            if ($grapher->hasGraph(
                $object->host_name,
                $object->service_description
            )) {
                $this->view->preview_image = $grapher->getPreviewImage(
                    $object->host_name,
                    $object->service_description
                );
            }
        }
    }
    /**
     * Creating tabs for this controller
     * @return \Icinga\Web\Widget\AbstractWidget
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
        $tabs->add(
            'host',
            array(
                'title' => '{{HOST_ICON}} Host',
                'url' => 'monitoring/show/host',
                'urlParams' => $params,
            )
        );
        if (!isset($this->view->service)) {
            $tabs->add(
                'services',
                array(
                    'title' => '{{SERVICE_ICON}} Services',
                    'url' => 'monitoring/show/services',
                    'urlParams' => $params,
                )
            );
        }
        if (isset($params['service'])) {
            $tabs->add(
                'service',
                array(
                    'title' => '{{SERVICE_ICON}} Service',
                    'url' => 'monitoring/show/service',
                    'urlParams' => $params,
                )
            );
        }
        $tabs->add(
            'history',
            array(
                'title' => '{{HISTORY_ICON}} History',
                'url' => 'monitoring/show/history',
                'urlParams' => $params,
            )
        );



        $tabs->extend(new OutputFormat())
            ->extend(new DashboardAction())
            ->extend(new BasketAction);

        /**
        $tabs->add('contacts', array(
            'title'     => 'Contacts',
            'icon'      => 'img/classic/customer.png',
            'url'       => 'monitoring/detail/contacts',
            'urlParams' => $params,
        ));**/

    }
}

// @codingStandardsIgnoreEnd
