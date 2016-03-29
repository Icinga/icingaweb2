<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\SendCustomNotificationCommandForm;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Web\Controller\MonitoredObjectController;
use Icinga\Web\Hook;
use Icinga\Web\Navigation\Navigation;

class ServiceController extends MonitoredObjectController
{
    /**
     * {@inheritdoc}
     */
    protected $commandRedirectUrl = 'monitoring/service/show';

    /**
     * Fetch the requested service from the monitoring backend
     */
    public function init()
    {
        $service = new Service(
            $this->backend, $this->params->getRequired('host'), $this->params->getRequired('service')
        );

        $this->applyRestriction('monitoring/filter/objects', $service);

        if ($service->fetch() === false) {
            $this->httpNotFound($this->translate('Service not found'));
        }
        $this->object = $service;
        $this->createTabs();
        $this->getTabs()->activate('service');
    }

    /**
     * Get service actions from hook
     *
     * @return Navigation
     */
    protected function getServiceActions()
    {
        $navigation = new Navigation();
        foreach (Hook::all('Monitoring\\ServiceActions') as $hook) {
            $navigation->merge($hook->getNavigation($this->object));
        }

        return $navigation;
    }

    /**
     * Show a service
     */
    public function showAction()
    {
        $this->view->actions = $this->getServiceActions();
        parent::showAction();
    }


    /**
     * Acknowledge a service problem
     */
    public function acknowledgeProblemAction()
    {
        $this->assertPermission('monitoring/command/acknowledge-problem');

        $form = new AcknowledgeProblemCommandForm();
        $form->setTitle($this->translate('Acknowledge Service Problem'));
        $this->handleCommandForm($form);
    }

    /**
     * Add a service comment
     */
    public function addCommentAction()
    {
        $this->assertPermission('monitoring/command/comment/add');

        $form = new AddCommentCommandForm();
        $form->setTitle($this->translate('Add Service Comment'));
        $this->handleCommandForm($form);
    }

    /**
     * Reschedule a service check
     */
    public function rescheduleCheckAction()
    {
        $this->assertPermission('monitoring/command/schedule-check');

        $form = new ScheduleServiceCheckCommandForm();
        $form->setTitle($this->translate('Reschedule Service Check'));
        $this->handleCommandForm($form);
    }

    /**
     * Schedule a service downtime
     */
    public function scheduleDowntimeAction()
    {
        $this->assertPermission('monitoring/command/downtime/schedule');

        $form = new ScheduleServiceDowntimeCommandForm();
        $form->setTitle($this->translate('Schedule Service Downtime'));
        $this->handleCommandForm($form);
    }

    /**
     * Submit a passive service check result
     */
    public function processCheckResultAction()
    {
        $this->assertPermission('monitoring/command/process-check-result');

        $form = new ProcessCheckResultCommandForm();
        $form->setTitle($this->translate('Submit Passive Service Check Result'));
        $this->handleCommandForm($form);
    }

    /**
     * Send a custom notification for a service
     */
    public function sendCustomNotificationAction()
    {
        $this->assertPermission('monitoring/command/send-custom-notification');

        $form = new SendCustomNotificationCommandForm();
        $form->setTitle($this->translate('Send Custom Service Notification'));
        $this->handleCommandForm($form);
    }
}
