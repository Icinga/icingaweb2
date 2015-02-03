<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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

        $this->applyRestriction('monitoring/services/filter', $service);

        if ($service->fetch() === false) {
            throw new Zend_Controller_Action_Exception($this->translate('Service not found'));
        }
        $this->object = $service;
        $this->createTabs();
        $this->getTabs()->activate('service');
    }

    /**
     * Acknowledge a service problem
     */
    public function acknowledgeProblemAction()
    {
        $this->assertPermission('monitoring/command/acknowledge-problem');

        $this->view->title = $this->translate('Acknowledge Service Problem');
        $this->handleCommandForm(new AcknowledgeProblemCommandForm());
    }

    /**
     * Add a service comment
     */
    public function addCommentAction()
    {
        $this->assertPermission('monitoring/command/comment/add');

        $this->view->title = $this->translate('Add Service Comment');
        $this->handleCommandForm(new AddCommentCommandForm());
    }

    /**
     * Reschedule a service check
     */
    public function rescheduleCheckAction()
    {
        $this->assertPermission('monitoring/command/schedule-check');

        $this->view->title = $this->translate('Reschedule Service Check');
        $this->handleCommandForm(new ScheduleServiceCheckCommandForm());
    }

    /**
     * Schedule a service downtime
     */
    public function scheduleDowntimeAction()
    {
        $this->assertPermission('monitoring/command/downtime/schedule');

        $this->view->title = $this->translate('Schedule Service Downtime');
        $this->handleCommandForm(new ScheduleServiceDowntimeCommandForm());
    }

    /**
     * Submit a passive service check result
     */
    public function processCheckResultAction()
    {
        $this->assertPermission('monitoring/command/process-check-result');

        $this->view->title = $this->translate('Submit Passive Service Check Result');
        $this->handleCommandForm(new ProcessCheckResultCommandForm());
    }
}
