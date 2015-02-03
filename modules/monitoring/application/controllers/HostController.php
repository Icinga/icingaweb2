<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostDowntimeCommandForm;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Web\Controller\MonitoredObjectController;
use Icinga\Web\Hook;

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

        $this->applyRestriction('monitoring/hosts/filter', $host);

        if ($host->fetch() === false) {
            throw new Zend_Controller_Action_Exception($this->translate('Host not found'));
        }
        $this->object = $host;
        $this->createTabs();
        $this->getTabs()->activate('host');
    }

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
        $this->view->hostActions = $this->getHostActions();
        parent::showAction();
    }

    /**
     * Acknowledge a host problem
     */
    public function acknowledgeProblemAction()
    {
        $this->assertPermission('monitoring/command/acknowledge-problem');

        $this->view->title = $this->translate('Acknowledge Host Problem');
        $this->handleCommandForm(new AcknowledgeProblemCommandForm());
    }

    /**
     * Add a host comment
     */
    public function addCommentAction()
    {
        $this->assertPermission('monitoring/command/comment/add');

        $this->view->title = $this->translate('Add Host Comment');
        $this->handleCommandForm(new AddCommentCommandForm());
    }

    /**
     * Reschedule a host check
     */
    public function rescheduleCheckAction()
    {
        $this->assertPermission('monitoring/command/schedule-check');

        $this->view->title = $this->translate('Reschedule Host Check');
        $this->handleCommandForm(new ScheduleHostCheckCommandForm());
    }

    /**
     * Schedule a host downtime
     */
    public function scheduleDowntimeAction()
    {
        $this->assertPermission('monitoring/command/downtime/schedule');

        $this->view->title = $this->translate('Schedule Host Downtime');
        $this->handleCommandForm(new ScheduleHostDowntimeCommandForm());
    }

    /**
     * Submit a passive host check result
     */
    public function processCheckResultAction()
    {
        $this->assertPermission('monitoring/command/process-check-result');

        $this->view->title = $this->translate('Submit Passive Host Check Result');
        $this->handleCommandForm(new ProcessCheckResultCommandForm());
    }
}
