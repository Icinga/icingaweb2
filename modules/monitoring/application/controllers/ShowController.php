<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Benchmark;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Web\Hook;
use Icinga\Web\Widget\Tabs;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Controller;
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
     * @var Hook\GrapherHook
     */
    protected $grapher;

    /**
     * Initialize the controller
     */
    public function init()
    {
        if ($this->getRequest()->getActionName() === 'host') {
            $this->view->object = new Host($this->params);
        } elseif ($this->getRequest()->getActionName() === 'service') {
            $this->view->object = new Service($this->params);
        } else {
            // TODO: Well... this could be done better
            $this->view->object = MonitoredObject::fromParams($this->params);
        }
        if (Hook::has('ticket')) {
            $this->view->tickets = Hook::first('ticket');
        }
        if (Hook::has('grapher')) {
            $this->grapher = Hook::first('grapher');
        }

        $this->createTabs();
    }

    /**
     * Service overview
     */
    public function serviceAction()
    {
        $o = $this->view->object;
        $this->setAutorefreshInterval(10);
        $this->view->title = $o->service_description
            . ' on ' . $o->host_name;
        $this->getTabs()->activate('service');
        $o->populate();
        if ($this->grapher && $this->grapher->hasPreviews($o->host_name, $o->service_description)) {
            $this->view->grapherHtml = $this->grapher->getPreviewHtml($o->host_name, $o->service_description);
        }
    }

    /**
     * Host overview
     */
    public function hostAction()
    {
        $o = $this->view->object;
        $this->setAutorefreshInterval(10);
        $this->getTabs()->activate('host');
        $this->view->title = $o->host_name;
        $o->populate();
        if ($this->grapher && $this->grapher->hasPreviews($o->host_name)) {
            $this->view->grapherHtml = $this->grapher->getPreviewHtml($o->host_name);
        }
    }

    public function historyAction()
    {
        $this->getTabs()->activate('history');
        //$this->view->object->populate();
        $this->view->object->fetchEventHistory();
        $this->view->history = $this->view->object->eventhistory->paginate($this->params->get('limit', 50));
        $this->handleFormatRequest($this->view->object->eventhistory);
    }

    public function servicesAction()
    {
        $this->getTabs()->activate('services');
        $this->_setParam('service', '');
        // TODO: This used to be a hack and still is. Modifying query string here.
        $_SERVER['QUERY_STRING'] = (string) $this->params->without('service')->set('limit', '');
        $this->view->services = $this->view->action('services', 'list', 'monitoring', array(
            'view'  => 'compact',
            'sort'  => 'service_description',
        ));
    }

    public function contactAction()
    {
        $contactName = $this->getParam('contact');

        if (! $contactName) {
            throw new Zend_Controller_Action_Exception(
                $this->translate('The parameter `contact\' is required'),
                404
            );
        }

        $query = $this->backend->select()->from('contact', array(
            'contact_name',
            'contact_id',
            'contact_alias',
            'contact_email',
            'contact_pager',
            'contact_object_id',
            'contact_notify_service_timeperiod',
            'contact_notify_service_recovery',
            'contact_notify_service_warning',
            'contact_notify_service_critical',
            'contact_notify_service_unknown',
            'contact_notify_service_flapping',
            'contact_notify_service_downtime',
            'contact_notify_host_timeperiod',
            'contact_notify_host_recovery',
            'contact_notify_host_down',
            'contact_notify_host_unreachable',
            'contact_notify_host_flapping',
            'contact_notify_host_downtime',
        ));

        $query->where('contact_name', $contactName);

        $contact = $query->getQuery()->fetchRow();

        if ($contact) {
            $commands = $this->backend->select()->from('command', array(
                'command_line',
                'command_name'
            ))->where('contact_id', $contact->contact_id);

            $this->view->commands = $commands->paginate();

            $notifications = $this->backend->select()->from('notification', array(
                'host',
                'service',
                'notification_output',
                'notification_contact',
                'notification_start_time',
                'notification_state'
            ));

            $notifications->where('contact_object_id', $contact->contact_object_id);

            $this->view->compact = true;
            $this->view->notifications = $notifications->paginate();
        }

        $this->view->contact = $contact;
        $this->view->contactName = $contactName;
    }

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
