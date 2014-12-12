<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostDowntimeCommandForm;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Web\Controller\MonitoredObjectController;

class Monitoring_HostController extends MonitoredObjectController
{
    /**
     * (non-PHPDoc)
     * @see MonitoredObjectController::$commandRedirectUrl For the property documentation.
     */
    protected $commandRedirectUrl = 'monitoring/host/show';

    /**
     * Fetch the requested host from the monitoring backend
     *
     * @throws Zend_Controller_Action_Exception If the host was not found
     */
    public function init()
    {
        $host = new Host($this->backend, $this->params->get('host'));
        if ($host->fetch() === false) {
            throw new Zend_Controller_Action_Exception($this->translate('Host not found'));
        }
        $this->object = $host;
        $this->createTabs();
    }

    /**
     * Show a host
     */
    public function showAction()
    {
        $this->getTabs()->activate('host');
        parent::showAction();
    }

    /**
     * Acknowledge a host problem
     */
    public function acknowledgeProblemAction()
    {
        $this->view->title = $this->translate('Acknowledge Host Problem');
        $this->handleCommandForm(new AcknowledgeProblemCommandForm());
    }

    /**
     * Add a host comment
     */
    public function addCommentAction()
    {
        $this->view->title = $this->translate('Add Host Comment');
        $this->handleCommandForm(new AddCommentCommandForm());
    }

    /**
     * Reschedule a host check
     */
    public function rescheduleCheckAction()
    {
        $this->view->title = $this->translate('Reschedule Host Check');
        $this->handleCommandForm(new ScheduleHostCheckCommandForm());
    }

    /**
     * Schedule a host downtime
     */
    public function scheduleDowntimeAction()
    {
        $this->view->title = $this->translate('Schedule Host Downtime');
        $this->handleCommandForm(new ScheduleHostDowntimeCommandForm());
    }

    /**
     * Submit a passive host check result
     */
    public function processCheckResultAction()
    {
        $this->view->title = $this->translate('Submit Passive Host Check Result');
        $this->handleCommandForm(new ProcessCheckResultCommandForm());
    }
}
