<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Web\Url;
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

    /**
     * @deprecated
     */
    public function historyAction()
    {
        if ($this->params->has('service')) {
            $this->redirectNow(Url::fromRequest()->setPath('monitoring/service/history'));
        }

        $this->redirectNow(Url::fromRequest()->setPath('monitoring/host/history'));
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

            $this->view->commands = $commands;

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
            $this->view->notifications = $notifications;
            $this->setupLimitControl();
            $this->setupPaginationControl($this->view->notifications);
        }

        $this->view->contact = $contact;
        $this->view->contactName = $contactName;
    }
}
