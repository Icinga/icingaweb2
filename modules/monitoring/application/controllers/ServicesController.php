<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\CheckNowCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ObjectsCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\RemoveAcknowledgementCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceDowntimeCommandForm;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Object\ServiceList;
use Icinga\Web\Url;
use Icinga\Web\Widget\Chart\InlinePie;

class Monitoring_ServicesController extends Controller
{
    /**
     * @var ServiceList
     */
    protected $serviceList;

    public function init()
    {
        $serviceList = new ServiceList($this->backend);
        $serviceList->setFilter(Filter::fromQueryString((string) $this->params->without('service_problem', 'service_handled')));
        $this->serviceList = $serviceList;
    }

    protected function handleCommandForm(ObjectsCommandForm $form)
    {
        $form
            ->setObjects($this->serviceList)
            ->setRedirectUrl(Url::fromPath('monitoring/services/show')->setParams($this->params))
            ->handleRequest();
        $this->view->form = $form;
        $this->_helper->viewRenderer('partials/command-form', null, true);
        return $form;
    }

    public function showAction()
    {
        $this->getTabs()->add(
            'show',
            array(
                'title' => mt('monitoring', 'Services'),
                'url' => Url::fromRequest()
            )
        )->activate('show');
        $this->setAutorefreshInterval(15);
        $checkNowForm = new CheckNowCommandForm();
        $checkNowForm
            ->setObjects($this->serviceList)
            ->handleRequest();
        $this->view->checkNowForm = $checkNowForm;
        $this->serviceList->setColumns(array(
            'host_name',
            'host_state',
            'service_description',
            'service_state',
            'service_problem',
            'service_handled',
            'service_acknowledged',
            'service_in_downtime'/*,
            'service_passive_checks_enabled',
            'service_notifications_enabled',
            'service_event_handler_enabled',
            'service_flap_detection_enabled',
            'service_active_checks_enabled',
            'service_obsessing'*/
        ));
        $unhandledObjects = array();
        $acknowledgedObjects = array();
        $objectsInDowntime = array();
        $serviceStates = array(
            Service::getStateText(Service::STATE_OK) => 0,
            Service::getStateText(Service::STATE_WARNING) => 0,
            Service::getStateText(Service::STATE_CRITICAL) => 0,
            Service::getStateText(Service::STATE_UNKNOWN) => 0,
            Service::getStateText(Service::STATE_PENDING) => 0
        );
        $knownHostStates = array();
        $hostStates = array(
            Host::getStateText(Host::STATE_UP) => 0,
            Host::getStateText(Host::STATE_DOWN) => 0,
            Host::getStateText(Host::STATE_UNREACHABLE) => 0,
            Host::getStateText(Host::STATE_PENDING) => 0,
        );
        foreach ($this->serviceList as $service) {
            /** @var Service $service */
            if ((bool) $service->problem === true && (bool) $service->handled === false) {
                $unhandledObjects[] = $service;
            }
            if ((bool) $service->acknowledged === true) {
                $acknowledgedObjects[] = $service;
            }
            if ((bool) $service->in_downtime === true) {
                $objectsInDowntime[] = $service;
            }
            ++$serviceStates[$service::getStateText($service->state)];
            if (! isset($knownHostStates[$service->getHost()->getName()])) {
                $knownHostStates[$service->getHost()->getName()] = true;
                ++$hostStates[$service->getHost()->getStateText($service->host_state)];
            }
        }
        if (! empty($acknowledgedObjects)) {
            $removeAckForm = new RemoveAcknowledgementCommandForm();
            $removeAckForm
                ->setObjects($acknowledgedObjects)
                ->handleRequest();
            $this->view->removeAckForm = $removeAckForm;
        }
        $this->setAutorefreshInterval(15);
        $this->view->listAllLink = Url::fromRequest()->setPath('monitoring/list/services');
        $this->view->rescheduleAllLink = Url::fromRequest()->setPath('monitoring/services/reschedule-check');
        $this->view->downtimeAllLink = Url::fromRequest()->setPath('monitoring/services/schedule-downtime');
        $this->view->processCheckResultAllLink = Url::fromRequest()->setPath(
            'monitoring/services/process-check-result'
        );
        $this->view->hostStates = $hostStates;
        $this->view->serviceStates = $serviceStates;
        $this->view->objects = $this->serviceList;
        $this->view->unhandledObjects = $unhandledObjects;
        $this->view->acknowledgeUnhandledLink = Url::fromRequest()
            ->setPath('monitoring/services/acknowledge-problem')
            ->addParams(array('service_problem' => 1, 'service_handled' => 0));
        $this->view->downtimeUnhandledLink = Url::fromRequest()
            ->setPath('monitoring/services/schedule-downtime')
            ->addParams(array('service_problem' => 1, 'service_handled' => 0));
        $this->view->acknowledgedObjects = $acknowledgedObjects;
        $this->view->objectsInDowntime = $objectsInDowntime;
        $this->view->inDowntimeLink = Url::fromRequest()
            ->setPath('monitoring/list/downtimes');
        $this->view->havingCommentsLink = Url::fromRequest()
            ->setPath('monitoring/list/comments');
        $this->view->serviceStatesPieChart = $this->createPieChart(
            $serviceStates,
            $this->translate('Service State'),
            array('#44bb77', '#FFCC66', '#FF5566', '#E066FF', '#77AAFF')
        );
        $this->view->hostStatesPieChart = $this->createPieChart(
            $hostStates,
            $this->translate('Host State'),
            array('#44bb77', '#FF5566', '#E066FF', '#77AAFF')
        );
    }

    protected function createPieChart(array $states, $title, array $colors)
    {
        $chart = new InlinePie(array_values($states), $title, $colors);
        return $chart
            ->setLabel(array_map('strtoupper', array_keys($states)))
            ->setHeight(100)
            ->setWidth(100)
            ->setTitle($title);
    }

    /**
     * Acknowledge service problems
     */
    public function acknowledgeProblemAction()
    {
        $this->view->title = $this->translate('Acknowledge Service Problems');
        $this->handleCommandForm(new AcknowledgeProblemCommandForm());
    }

    /**
     * Reschedule service checks
     */
    public function rescheduleCheckAction()
    {
        $this->view->title = $this->translate('Reschedule Service Checks');
        $this->handleCommandForm(new ScheduleServiceCheckCommandForm());
    }

    /**
     * Schedule service downtimes
     */
    public function scheduleDowntimeAction()
    {
        $this->view->title = $this->translate('Schedule Service Downtimes');
        $this->handleCommandForm(new ScheduleServiceDowntimeCommandForm());
    }

    /**
     * Submit passive service check results
     */
    public function processCheckResultAction()
    {
        $this->view->title = $this->translate('Submit Passive Service Check Results');
        $this->handleCommandForm(new ProcessCheckResultCommandForm());
    }
}
