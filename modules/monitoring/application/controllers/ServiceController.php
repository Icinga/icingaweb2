<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceDowntimeCommandForm;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Web\Controller\MonitoredObjectController;

class Monitoring_ServiceController extends MonitoredObjectController
{
    /**
     * (non-PHPDoc)
     * @see MonitoredObjectController::$commandRedirectUrl For the property documentation.
     */
    protected $commandRedirectUrl = 'monitoring/service/show';

    /**
     * Fetch the requested service from the monitoring backend
     *
     * @throws Zend_Controller_Action_Exception If the service was not found
     */
    public function init()
    {
        $service = new Service($this->backend, $this->params->get('host'), $this->params->get('service'));
        if ($service->fetch() === false) {
            throw new Zend_Controller_Action_Exception($this->translate('Service not found'));
        }
        $this->object = $service;
        $this->createTabs();
    }

    /**
     * Show a service
     */
    public function showAction()
    {
        $this->getTabs()->activate('service');
        parent::showAction();
    }

    /**
     * Acknowledge a service problem
     */
    public function acknowledgeProblemAction()
    {
        $this->view->title = $this->translate('Acknowledge Service Problem');
        $this->handleCommandForm(new AcknowledgeProblemCommandForm());
    }

    /**
     * Add a service comment
     */
    public function addCommentAction()
    {
        $this->view->title = $this->translate('Add Service Comment');
        $this->handleCommandForm(new AddCommentCommandForm());
    }

    /**
     * Reschedule a service check
     */
    public function rescheduleCheckAction()
    {
        $this->view->title = $this->translate('Reschedule Service Check');
        $this->handleCommandForm(new ScheduleServiceCheckCommandForm());
    }

    /**
     * Schedule a service downtime
     */
    public function scheduleDowntimeAction()
    {
        $this->view->title = $this->translate('Schedule Service Downtime');
        $this->handleCommandForm(new ScheduleServiceDowntimeCommandForm());
    }

    /**
     * Submit a passive service check result
     */
    public function processCheckResultAction()
    {
        $this->view->title = $this->translate('Submit Passive Service Check Result');
        $this->handleCommandForm(new ProcessCheckResultCommandForm());
    }
}
