<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Data\Filter\FilterEqual;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\DataView\DataView;
use Icinga\Security\SecurityException;
use Icinga\Web\Url;

/**
 * Class Monitoring_ShowController
 *
 * Actions for show context
 */
class ShowController extends Controller
{
    /**
     * @var MonitoringBackend
     */
    protected $backend;

    public function init()
    {
        $this->view->defaultTitle = $this->translate('Contacts') . ' :: ' . $this->view->defaultTitle;

        parent::init();
    }

    public function contactAction()
    {
        if (! $this->hasPermission('*') && $this->hasPermission('no-monitoring/contacts')) {
            throw new SecurityException('No permission for %s', 'monitoring/contacts');
        }

        $contactName = $this->params->getRequired('contact_name');

        $this->getTabs()->add('contact-detail', [
            'title'  => $this->translate('Contact details'),
            'label'  => $this->translate('Contact'),
            'url'    => Url::fromRequest(),
            'active' => true
        ]);

        $query = $this->backend->select()->from('contact', array(
            'contact_name',
            'contact_id',
            'contact_alias',
            'contact_email',
            'contact_pager',
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
        $this->applyRestriction('monitoring/filter/objects', $query);
        $query->whereEx(new FilterEqual('contact_name', '=', $contactName));
        $contact = $query->getQuery()->fetchRow();

        if ($contact) {
            $commands = $this->backend->select()->from('command', array(
                'command_line',
                'command_name'
            ))->where('contact_id', $contact->contact_id);

            $this->view->commands = $commands;

            $notifications = $this->backend->select()->from('notification', array(
                'id',
                'host_name',
                'service_description',
                'notification_output',
                'notification_contact_name',
                'notification_timestamp',
                'notification_state',
                'host_display_name',
                'service_display_name'
            ));

            $notifications->where('notification_contact_name', $contactName);
            $this->applyRestriction('monitoring/filter/objects', $notifications);
            $this->view->notifications = $notifications;
            $this->setupLimitControl();
            $this->setupPaginationControl($notifications);
            $this->view->title = $contact->contact_name;
        }

        $this->view->contact = $contact;
        $this->view->contactName = $contactName;
    }
}
