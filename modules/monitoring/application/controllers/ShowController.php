<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Web\Hook;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabs;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Controller;

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
        $this->view->object = MonitoredObject::fromParams($this->params);
        if ($this->view->object && $this->view->object->fetch() === false) {
            throw new Zend_Controller_Action_Exception($this->translate('Host or service not found'));
        }

        if (Hook::has('ticket')) {
            $this->view->tickets = Hook::first('ticket');
        }
        if (Hook::has('grapher')) {
            $this->grapher = Hook::first('grapher');
            if ($this->grapher && ! $this->grapher->hasPreviews()) {
                $this->grapher = null;
            }
        }

        $this->createTabs();
    }

    /**
     * @deprecated
     */
    public function serviceAction()
    {
        $this->redirectNow(Url::fromRequest()->setPath('monitoring/service/show'));
    }

    /**
     * @deprecated
     */
    public function hostAction()
    {
        $this->redirectNow(Url::fromRequest()->setPath('monitoring/host/show'));
    }

    public function historyAction()
    {
        $this->getTabs()->activate('history');
        $this->view->object->fetchEventHistory();
        $this->view->history = $this->view->object->eventhistory->getQuery()->paginate($this->params->get('limit', 50));
        $this->handleFormatRequest($this->view->object->eventhistory);
        $this->fetchHostStats();

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->history);
    }

    public function servicesAction()
    {
        $this->setAutorefreshInterval(15);
        $this->getTabs()->activate('services');
        $this->_setParam('service', '');
        // TODO: This used to be a hack and still is. Modifying query string here.
        $_SERVER['QUERY_STRING'] = (string) $this->params->without('service')->set('limit', '');
        $this->view->services = $this->view->action('services', 'list', 'monitoring', array(
            'view'  => 'compact',
            'sort'  => 'service_description',
        ));
        $this->fetchHostStats();
    }

    protected function fetchHostStats()
    {
        $this->view->stats = $this->backend->select()->from('statusSummary', array(
            'services_total',
            'services_ok',
            'services_problem',
            'services_problem_handled',
            'services_problem_unhandled',
            'services_critical',
            'services_critical_unhandled',
            'services_critical_handled',
            'services_warning',
            'services_warning_unhandled',
            'services_warning_handled',
            'services_unknown',
            'services_unknown_unhandled',
            'services_unknown_handled',
            'services_pending',
        ))->where('service_host_name', $this->params->get('host'))->getQuery()->fetchRow();
    }

    public function contactAction()
    {
        $contactName = $this->getParam('contact_name');

        if (! $contactName) {
            throw new Zend_Controller_Action_Exception(
                $this->translate('The parameter `contact_name\' is required'),
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
                'host_name',
                'service_description',
                'notification_output',
                'notification_contact_name',
                'notification_start_time',
                'notification_state',
                'host_display_name',
                'service_display_name'
            ));

            $notifications->where('contact_object_id', $contact->contact_object_id);
            $this->view->notifications = $notifications->paginate();
            $this->setupLimitControl();
            $this->setupPaginationControl($this->view->notifications);
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
        if ($object->getType() === $object::TYPE_HOST) {
            $isService = false;
            $params = array(
                'host' => $object->getName()
            );
        } else {
            $isService = true;
            $params = array(
                'host'      => $object->getHost()->getName(),
                'service'   => $object->getName()
            );
        }
        $tabs = $this->getTabs();
        $tabs->add(
            'host',
            array(
                'title'     => sprintf(
                    $this->translate('Show detailed information for host %s'),
                    $isService ? $object->getHost()->getName() : $object->getName()
                ),
                'label'     => $this->translate('Host'),
                'icon'      => 'host',
                'url'       => 'monitoring/show/host',
                'urlParams' => $params,
            )
        );
        if ($isService) {
            $tabs->add(
                'service',
                array(
                    'title'     => sprintf(
                        $this->translate('Show detailed information for service %s on host %s'),
                        $object->getName(),
                        $object->getHost()->getName()
                    ),
                    'label'     => $this->translate('Service'),
                    'icon'      => 'service',
                    'url'       => 'monitoring/show/service',
                    'urlParams' => $params,
                )
            );
        }
        $tabs->add(
            'services',
            array(
                'title'     => sprintf(
                    $this->translate('List all services on host %s'),
                    $isService ? $object->getHost()->getName() : $object->getName()
                ),
                'label'     => $this->translate('Services'),
                'icon'      => 'services',
                'url'       => 'monitoring/show/services',
                'urlParams' => $params,
            )
        );
        if ($this->backend->hasQuery('eventHistory')) {
            $tabs->add(
                'history',
                array(
                    'title'     => $isService
                        ? sprintf(
                            $this->translate('Show all event records of service %s on host %s'),
                            $object->getName(),
                            $object->getHost()->getName()
                        )
                        : sprintf($this->translate('Show all event records of host %s'), $object->getName())
                    ,
                    'label'     => $this->translate('History'),
                    'icon'      => 'rewind',
                    'url'       => 'monitoring/show/history',
                    'urlParams' => $params,
                )
            );
        }
        $tabs->extend(new OutputFormat())
            ->extend(new DashboardAction());
    }
}
