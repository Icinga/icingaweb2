<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\CheckNowCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ObjectsCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\RemoveAcknowledgementCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\SendCustomNotificationCommandForm;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\HostList;
use Icinga\Web\Url;
use Icinga\Web\Widget\Chart\InlinePie;
use Icinga\Web\Widget\Tabextension\DashboardAction;

class Monitoring_HostsController extends Controller
{
    /**
     * @var HostList
     */
    protected $hostList;

    public function init()
    {
        $hostList = new HostList($this->backend);
        $hostList->setFilter(Filter::fromQueryString((string) $this->params->without('view')));
        $this->hostList = $hostList;
    }

    protected function handleCommandForm(ObjectsCommandForm $form)
    {
        $this->hostList->setColumns(array(
            'host_name',
            'host_state',
            'host_problem',
            'host_handled',
            'host_acknowledged',
            'host_in_downtime'
        ));

        $form
            ->setObjects($this->hostList)
            ->setRedirectUrl(Url::fromPath('monitoring/hosts/show')->setParams($this->params))
            ->handleRequest();

        $hostStates = array(
            Host::getStateText(Host::STATE_UP) => 0,
            Host::getStateText(Host::STATE_DOWN) => 0,
            Host::getStateText(Host::STATE_UNREACHABLE) => 0,
            Host::getStateText(Host::STATE_PENDING) => 0,
        );
        foreach ($this->hostList as $host) {
            ++$hostStates[$host::getStateText($host->state)];
        }

        $this->view->form = $form;
        $this->view->objects = $this->hostList;
        $this->view->hostStates = $hostStates;
        $this->view->hostStatesPieChart = $this->createPieChart(
            $hostStates,
            $this->translate('Host State'),
            array('#44bb77', '#FF5566', '#E066FF', '#77AAFF')
        );
        $this->_helper->viewRenderer('partials/command/objects-command-form', null, true);
        return $form;
    }

    public function showAction()
    {
        $this->getTabs()->add(
            'show',
            array(
                'title' => sprintf(
                    $this->translate('Show summarized information for %u hosts'),
                    count($this->hostList)
                ),
                'label' => $this->translate('Hosts'),
                'url'   => Url::fromRequest()
            )
        )->extend(new DashboardAction())->activate('show');
        $this->setAutorefreshInterval(15);
        $checkNowForm = new CheckNowCommandForm();
        $checkNowForm
            ->setObjects($this->hostList)
            ->handleRequest();
        $this->view->checkNowForm = $checkNowForm;
        $this->hostList->setColumns(array(
            'host_name',
            'host_state',
            'host_problem',
            'host_handled',
            'host_acknowledged',
            'host_in_downtime'/*,
            'host_passive_checks_enabled',
            'host_notifications_enabled',
            'host_event_handler_enabled',
            'host_flap_detection_enabled',
            'host_active_checks_enabled',
            'host_obsessing'*/
        ));
        $unhandledObjects = array();
        $unhandledFilterExpressions = array();
        $acknowledgedObjects = array();
        $objectsInDowntime = array();
        $downtimeFilterExpressions = array();
        $hostStates = array(
            Host::getStateText(Host::STATE_UP) => 0,
            Host::getStateText(Host::STATE_DOWN) => 0,
            Host::getStateText(Host::STATE_UNREACHABLE) => 0,
            Host::getStateText(Host::STATE_PENDING) => 0,
        );
        foreach ($this->hostList as $host) {
            /** @var Host $host */
            if ((bool) $host->problem === true && (bool) $host->handled === false) {
                $unhandledObjects[] = $host;
                $unhandledFilterExpressions[] = Filter::where('host', $host->getName());
            }
            if ((bool) $host->acknowledged === true) {
                $acknowledgedObjects[] = $host;
            }
            if ((bool) $host->in_downtime === true) {
                $objectsInDowntime[] = $host;
                $downtimeFilterExpressions[] = Filter::where('host_name', $host->getName());
            }
            ++$hostStates[$host::getStateText($host->state)];
        }
        if (! empty($acknowledgedObjects)) {
            $removeAckForm = new RemoveAcknowledgementCommandForm();
            $removeAckForm
                ->setObjects($acknowledgedObjects)
                ->handleRequest();
            $this->view->removeAckForm = $removeAckForm;
        }
        $this->setAutorefreshInterval(15);
        $this->view->listAllLink = Url::fromRequest()->setPath('monitoring/list/hosts');
        $this->view->rescheduleAllLink = Url::fromRequest()->setPath('monitoring/hosts/reschedule-check');
        $this->view->downtimeAllLink = Url::fromRequest()->setPath('monitoring/hosts/schedule-downtime');
        $this->view->processCheckResultAllLink = Url::fromRequest()->setPath('monitoring/hosts/process-check-result');
        $this->view->hostStates = $hostStates;
        $this->view->objects = $this->hostList;
        $this->view->unhandledObjects = $unhandledObjects;
        $unhandledFilterQueryString = Filter::matchAny($unhandledFilterExpressions)->toQueryString();
        $this->view->acknowledgeUnhandledLink = Url::fromPath('monitoring/hosts/acknowledge-problem')
            ->setQueryString($unhandledFilterQueryString);
        $this->view->downtimeUnhandledLink = Url::fromPath('monitoring/hosts/schedule-downtime')
            ->setQueryString($unhandledFilterQueryString);
        $this->view->acknowledgedObjects = $acknowledgedObjects;
        $this->view->objectsInDowntime = $objectsInDowntime;
        $this->view->inDowntimeLink = Url::fromPath('monitoring/list/downtimes')
            ->setQueryString(Filter::matchAny($downtimeFilterExpressions)->toQueryString());
        $this->view->commentsLink = Url::fromRequest()
            ->setPath('monitoring/list/comments');
        $this->view->hostStatesPieChart = $this->createPieChart(
            $hostStates,
            $this->translate('Host State'),
            array('#44bb77', '#FF5566', '#E066FF', '#77AAFF')
        );
        $this->view->sendCustomNotificationLink =
            Url::fromRequest()->setPath(
                'monitoring/hosts/send-custom-notification'
            );
    }

    protected function createPieChart(array $states, $title, array $colors)
    {
        $chart = new InlinePie(array_values($states), $title, $colors);
        return $chart
            ->setSize(75)
            ->setTitle('')
            ->setSparklineClass('sparkline-multi');
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
