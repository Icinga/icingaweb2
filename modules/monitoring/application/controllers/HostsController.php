<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterEqual;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\CheckNowCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ObjectsCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\RemoveAcknowledgementCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\SendCustomNotificationCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ToggleObjectFeaturesCommandForm;
use Icinga\Module\Monitoring\Object\HostList;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;

class HostsController extends Controller
{
    /**
     * @var HostList
     */
    protected $hostList;

    public function init()
    {
        $hostList = new HostList($this->backend);
        $this->applyRestriction('monitoring/filter/objects', $hostList);
        $hostList->addFilter(Filter::fromQueryString((string) $this->params));
        $this->hostList = $hostList;
        $this->hostList->setColumns(array(
            'host_acknowledged',
            'host_active_checks_enabled',
            'host_display_name',
            'host_event_handler_enabled',
            'host_flap_detection_enabled',
            'host_handled',
            'host_in_downtime',
            'host_is_flapping',
            'host_last_state_change',
            'host_name',
            'host_notifications_enabled',
            'host_obsessing',
            'host_passive_checks_enabled',
            'host_problem',
            'host_state',
            'instance_name'
        ));
        $this->view->baseFilter = $this->hostList->getFilter();
        $this->getTabs()->add(
            'show',
            array(
                'label' => $this->translate('Hosts') . sprintf(' (%d)', count($this->hostList)),
                'title' => sprintf(
                    $this->translate('Show summarized information for %u hosts'),
                    count($this->hostList)
                ),
                'url'   => Url::fromRequest()
            )
        )->extend(new DashboardAction())->extend(new MenuAction())->activate('show');
        $this->view->listAllLink = Url::fromRequest()->setPath('monitoring/list/hosts');
    }

    protected function handleCommandForm(ObjectsCommandForm $form)
    {
        $form
            ->setBackend($this->backend)
            ->setObjects($this->hostList)
            ->setRedirectUrl(Url::fromPath('monitoring/hosts/show')->setParams($this->params))
            ->handleRequest();

        $this->view->form = $form;
        $this->view->objects = $this->hostList;
        $this->view->stats = $this->hostList->getStateSummary();
        $this->_helper->viewRenderer('partials/command/objects-command-form', null, true);
        return $form;
    }

    public function showAction()
    {
        $this->setAutorefreshInterval(15);
        if ($this->Auth()->hasPermission('monitoring/command/schedule-check')) {
            $checkNowForm = new CheckNowCommandForm();
            $checkNowForm
                ->setObjects($this->hostList)
                ->handleRequest();
            $this->view->checkNowForm = $checkNowForm;
        }

        $acknowledgedObjects = $this->hostList->getAcknowledgedObjects();
        if (! empty($acknowledgedObjects)) {
            $removeAckForm = new RemoveAcknowledgementCommandForm();
            $removeAckForm
                ->setObjects($acknowledgedObjects)
                ->handleRequest();
            $this->view->removeAckForm = $removeAckForm;
        }

        $featureStatus = $this->hostList->getFeatureStatus();
        $toggleFeaturesForm = new ToggleObjectFeaturesCommandForm(array(
            'backend'   => $this->backend,
            'objects'   => $this->hostList
        ));
        $toggleFeaturesForm
            ->load((object) $featureStatus)
            ->handleRequest();
        $this->view->toggleFeaturesForm = $toggleFeaturesForm;

        $hostStates = $this->hostList->getStateSummary();

        $this->setAutorefreshInterval(15);
        $this->view->rescheduleAllLink = Url::fromRequest()->setPath('monitoring/hosts/reschedule-check');
        $this->view->downtimeAllLink = Url::fromRequest()->setPath('monitoring/hosts/schedule-downtime');
        $this->view->processCheckResultAllLink = Url::fromRequest()->setPath('monitoring/hosts/process-check-result');
        $this->view->addCommentLink = Url::fromRequest()->setPath('monitoring/hosts/add-comment');
        $this->view->stats = $hostStates;
        $this->view->objects = $this->hostList;
        $this->view->unhandledObjects = $this->hostList->getUnhandledObjects();
        $this->view->problemObjects = $this->hostList->getProblemObjects();
        $this->view->acknowledgeUnhandledLink = Url::fromPath('monitoring/hosts/acknowledge-problem')
            ->setQueryString($this->hostList->getUnhandledObjects()->objectsFilter()->toQueryString());
        $this->view->downtimeUnhandledLink = Url::fromPath('monitoring/hosts/schedule-downtime')
            ->setQueryString($this->hostList->getUnhandledObjects()->objectsFilter()->toQueryString());
        $this->view->downtimeLink = Url::fromPath('monitoring/hosts/schedule-downtime')
            ->setQueryString($this->hostList->getProblemObjects()->objectsFilter()->toQueryString());
        $this->view->acknowledgedObjects = $this->hostList->getAcknowledgedObjects();
        $this->view->acknowledgeLink = Url::fromPath('monitoring/hosts/acknowledge-problem')
            ->setQueryString($this->hostList->getUnacknowledgedObjects()->objectsFilter()->toQueryString());
        $this->view->unacknowledgedObjects = $this->hostList->getUnacknowledgedObjects();
        $this->view->objectsInDowntime = $this->hostList->getObjectsInDowntime();
        $this->view->inDowntimeLink = Url::fromPath('monitoring/list/hosts')
            ->setQueryString(
                $this->hostList
                    ->getObjectsInDowntime()
                    ->objectsFilter()
                    ->toQueryString()
            );
        $this->view->showDowntimesLink = Url::fromPath('monitoring/list/downtimes')
            ->setQueryString(
                $this->hostList
                    ->objectsFilter()
                    ->andFilter(FilterEqual::where('object_type', 'host'))
                    ->toQueryString()
            );
        $this->view->commentsLink = Url::fromRequest()->setPath('monitoring/list/comments');
        $this->view->sendCustomNotificationLink = Url::fromRequest()->setPath('monitoring/hosts/send-custom-notification');
    }

    /**
     * Add a host comments
     */
    public function addCommentAction()
    {
        $this->assertPermission('monitoring/command/comment/add');

        $form = new AddCommentCommandForm();
        $form->setTitle($this->translate('Add Host Comments'));
        $this->handleCommandForm($form);
    }

    /**
     * Acknowledge host problems
     */
    public function acknowledgeProblemAction()
    {
        $this->assertPermission('monitoring/command/acknowledge-problem');

        $form = new AcknowledgeProblemCommandForm();
        $form->setTitle($this->translate('Acknowledge Host Problems'));
        $this->handleCommandForm($form);
    }

    /**
     * Reschedule host checks
     */
    public function rescheduleCheckAction()
    {
        $this->assertPermission('monitoring/command/schedule-check');

        $form = new ScheduleHostCheckCommandForm();
        $form->setTitle($this->translate('Reschedule Host Checks'));
        $this->handleCommandForm($form);
    }

    /**
     * Schedule host downtimes
     */
    public function scheduleDowntimeAction()
    {
        $this->assertPermission('monitoring/command/downtime/schedule');

        $form = new ScheduleHostDowntimeCommandForm();
        $form->setTitle($this->translate('Schedule Host Downtimes'));
        $this->handleCommandForm($form);
    }

    /**
     * Submit passive host check results
     */
    public function processCheckResultAction()
    {
        $this->assertPermission('monitoring/command/process-check-result');

        $form = new ProcessCheckResultCommandForm();
        $form->setTitle($this->translate('Submit Passive Host Check Results'));
        $this->handleCommandForm($form);
    }

    /**
     * Send a custom notification for hosts
     */
    public function sendCustomNotificationAction()
    {
        $this->assertPermission('monitoring/command/send-custom-notification');

        $form = new SendCustomNotificationCommandForm();
        $form->setTitle($this->translate('Send Custom Host Notification'));
        $this->handleCommandForm($form);
    }
}
