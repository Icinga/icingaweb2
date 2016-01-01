<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\SendCustomNotificationCommandForm;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Web\Controller\MonitoredObjectController;
use Icinga\Web\Hook;

class HostController extends MonitoredObjectController
{

    /**
     * {@inheritdoc}
     */
    protected $commandRedirectUrl = 'monitoring/host/show';

    /**
     * Fetch the requested host from the monitoring backend
     */
    public function init()
    {
        $host = new Host($this->backend, $this->params->getRequired('host'));
        $this->applyRestriction('monitoring/filter/objects', $host);
        if ($host->fetch() === false) {
            $this->httpNotFound($this->translate('Host not found'));
        }
        $this->object = $host;
        $this->createTabs();
        $this->getTabs()->activate('host');
    }

    /**
     * Get host actions from hook
     *
     * @return array
     */
    protected function getHostActions()
    {
        $urls = array();

        foreach (Hook::all('Monitoring\\HostActions') as $hook) {
            foreach ($hook->getActionsForHost($this->object) as $id => $url) {
                $urls[$id] = $url;
            }
        }

        return $urls;
    }

    /**
     * Show a host
     */
    public function showAction()
    {
        $this->view->actions = $this->getHostActions();
        parent::showAction();
    }

    /**
     * List a host's services
     */
    public function servicesAction()
    {
        $this->setAutorefreshInterval(10);
        $this->getTabs()->activate('services');
        $query = $this->backend->select()->from('servicestatus', array(
            'host_name',
            'host_display_name',
            'host_state',
            'host_state_type',
            'host_last_state_change',
            'host_address',
            'host_address6',
            'host_handled',
            'service_description',
            'service_display_name',
            'service_state',
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_output',
            'service_perfdata',
            'service_attempt',
            'service_last_state_change',
            'service_icon_image',
            'service_icon_image_alt',
            'service_is_flapping',
            'service_state_type',
            'service_handled',
            'service_severity',
            'service_last_check',
            'service_notifications_enabled',
            'service_action_url',
            'service_notes_url',
            'service_active_checks_enabled',
            'service_passive_checks_enabled',
            'current_check_attempt' => 'service_current_check_attempt',
            'max_check_attempts'    => 'service_max_check_attempts'
        ));
        $this->applyRestriction('monitoring/filter/objects', $query);
        $this->view->services = $query->where('host_name', $this->object->getName());
        $this->view->object = $this->object;
    }

    /**
     * Acknowledge a host problem
     */
    public function acknowledgeProblemAction()
    {
        $this->assertPermission('monitoring/command/acknowledge-problem');

        $form = new AcknowledgeProblemCommandForm();
        $form->setTitle($this->translate('Acknowledge Host Problem'));
        $this->handleCommandForm($form);
    }

    /**
     * Add a host comment
     */
    public function addCommentAction()
    {
        $this->assertPermission('monitoring/command/comment/add');

        $form = new AddCommentCommandForm();
        $form->setTitle($this->translate('Add Host Comment'));
        $this->handleCommandForm($form);
    }

    /**
     * Reschedule a host check
     */
    public function rescheduleCheckAction()
    {
        $this->assertPermission('monitoring/command/schedule-check');

        $form = new ScheduleHostCheckCommandForm();
        $form->setTitle($this->translate('Reschedule Host Check'));
        $this->handleCommandForm($form);
    }

    /**
     * Schedule a host downtime
     */
    public function scheduleDowntimeAction()
    {
        $this->assertPermission('monitoring/command/downtime/schedule');

        $form = new ScheduleHostDowntimeCommandForm();
        $form->setBackend($this->backend);
        $form->setTitle($this->translate('Schedule Host Downtime'));
        $this->handleCommandForm($form);
    }

    /**
     * Submit a passive host check result
     */
    public function processCheckResultAction()
    {
        $this->assertPermission('monitoring/command/process-check-result');

        $form = new ProcessCheckResultCommandForm();
        $form->setBackend($this->backend);
        $form->setTitle($this->translate('Submit Passive Host Check Result'));
        $this->handleCommandForm($form);
    }

    /**
     * Send a custom notification for host
     */
    public function sendCustomNotificationAction()
    {
        $this->assertPermission('monitoring/command/send-custom-notification');

        $form = new SendCustomNotificationCommandForm();
        $form->setTitle($this->translate('Send Custom Host Notification'));
        $this->handleCommandForm($form);
    }
}
