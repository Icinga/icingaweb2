<?php

use Icinga\Monitoring\Backend;
use Icinga\Web\ModuleActionController;
use Icinga\Web\Hook;
use Icinga\Monitoring\Object\Host;
use Icinga\Monitoring\Object\Service;

class Monitoring_ShowController extends ModuleActionController
{
    protected $backend;

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

        $this->view->compact = $this->_getParam('view') === 'compact';

        if ($object === null) {
            // TODO: Notification, not found
            $this->redirectNow('monitoring/list/services');
            return;
        }
        $this->view->object = $object;
        $this->view->tabs = $this->createTabs();
        $this->prepareTicketHook();
    }

    public function serviceAction()
    {
        $object = $this->view->object->prefetch();
        $this->prepareGrapherHook();
    }

    public function hostAction()
    {
        $this->view->object->prefetch();
        $this->prepareGrapherHook();
    }

    public function historyAction()
    {
        $this->view->history = $this->backend->select()
            ->from('eventHistory', array(
                'object_type',
                'host_name',
                'service_description',
                'timestamp',
                'state',
                'attempt',
                'max_attempts',
                'output',
                'type'
            ))->applyRequest($this->_request);

        $this->view->preserve = $this->view->history->getAppliedFilter()->toParams();
    }

    public function servicesAction()
    {
        $this->_setParam('service', null);
        // Ugly and slow:
        $this->view->services = $this->view->action('services', 'list', 'monitoring', array(
            'view' => 'compact'
        ));
    }

    public function ticketAction()
    {
        if (Hook::has('ticket')) {
            // TODO: Still hardcoded, should ask for URL:
            $id = $this->_getParam('ticket');
            $ticketModule = 'rt';
            $this->render();
            $this->_forward('ticket', 'show', $ticketModule, array(
                'id' => $id
            ));
        }
    }

    protected function prepareTicketHook()
    {
        if (Hook::has('ticket')) {
            $object = $this->view->object;
            $params = array(
                'host' => $object->host_name
            );
            if ($object instanceof Service) {
                $params['service'] = $object->service_description;
            }

            $params['ticket'] = '__ID__';
            $this->view->ticket_link = preg_replace(
                '~__ID__~',
                '\$1',
                $this->view->qlink('#__ID__',
                    'monitoring/show/ticket',
                    $params
                )
            );
            // TODO: Global ticket pattern config (or per environment)
            $this->view->ticket_pattern = '~#(\d{4,6})~';
        }
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

    protected function createTabs()
    {
        $object = $this->view->object;
        $tabs = $this->widget('tabs');
        $params = array('host' => $object->host_name);
        if ($backend = $this->_getParam('backend')) {
            $params['backend'] = $backend;
        }
        if ($object instanceof Service) {
            $params['service'] = $object->service_description;
        } elseif ($service = $this->_getParam('service')) {
            $params['service'] = $service;
        }
        // TODO: Work with URL
        $servicesParams = $params;
        unset($servicesParams['service']);
        $tabs->add('host', array(
            'title'     => 'Host',
            'icon'      => 'img/classic/server.png',
            'url'       => 'monitoring/show/host',
            'urlParams' => $params,
        ));
        $tabs->add('services', array(
            'title'     => 'Services',
            'icon'      => 'img/classic/service.png',
            'url'       => 'monitoring/show/services',
            'urlParams' => $servicesParams,
        ));
        if (isset($params['service'])) {
            $tabs->add('service', array(
                'title'     => 'Service',
                'icon'      => 'img/classic/service.png',
                'url'       => 'monitoring/show/service',
                'urlParams' => $params,
            ));
        }
        $tabs->add('history', array(
            'title'     => 'History',
            'icon'      => 'img/classic/history.gif',
            'url'       => 'monitoring/show/history',
            'urlParams' => $params,
        ));
        if ($this->action_name === 'ticket') {
            $tabs->add('ticket', array(
                'title'     => 'Ticket',
                'icon'      => 'img/classic/ticket.gif',
                'url'       => 'monitoring/show/ticket',
                'urlParams' => $params + array('ticket' => $this->_getParam('ticket')),
            ));
        }

        $tabs->activate($this->action_name)->enableSpecialActions();

/*
        $tabs->add('contacts', array(
            'title'     => 'Contacts',
            'icon'      => 'img/classic/customer.png',
            'url'       => 'monitoring/detail/contacts',
            'urlParams' => $params,
        ));
*/
        // TODO: Inventory 'img/classic/facts.gif'
        //       Ticket    'img/classic/ticket.gif'
        //       Customer  'img/classic/customer.png'
        return $tabs;
    }
}
