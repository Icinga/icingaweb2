<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\CheckNowCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ObjectsCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\RemoveAcknowledgementCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentCommandForm;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Object\ServiceList;
use Icinga\Web\Url;

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
        $this->view->listAllLink = Url::fromRequest()->setPath('monitoring/list/services');
        $this->getTabs()->add(
            'show',
            array(
                'title' => sprintf(
                    $this->translate('Show summarized information for %u services'),
                    count($this->serviceList)
                ),
                'label' => $this->translate('Services'),
                'url'   => Url::fromRequest(),
                'icon' => 'services'
            )
        )->activate('show');
    }

    protected function handleCommandForm(ObjectsCommandForm $form)
    {
        $this->serviceList->setColumns(array(
            'host_name',
            'host_state',
            'service_description',
            'service_state',
            'service_problem',
            'service_handled',
            'service_acknowledged',
            'service_in_downtime',
            'service_is_flapping',
            'service_notifications_enabled',
            'service_output',
            'service_last_ack',
            'service_last_comment'
        ));

        $form
            ->setObjects($this->serviceList)
            ->setRedirectUrl(Url::fromPath('monitoring/services/show')->setParams($this->params))
            ->handleRequest();

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
            ++$serviceStates[$service::getStateText($service->state)];
            if (! isset($knownHostStates[$service->getHost()->getName()])) {
                $knownHostStates[$service->getHost()->getName()] = true;
                ++$hostStates[$service->getHost()->getStateText($service->host_state)];
            }
        }

        $this->view->form = $form;
        $this->view->objects = $this->serviceList;
        $this->view->serviceStates = $serviceStates;
        $this->view->hostStates = $hostStates;
        $this->_helper->viewRenderer('partials/command/objects-command-form', null, true);
        return $form;
    }

    public function showAction()
    {
        $this->setAutorefreshInterval(15);
        $checkNowForm = new CheckNowCommandForm();
        $checkNowForm
            ->setObjects($this->serviceList)
            ->handleRequest();
        $this->view->checkNowForm = $checkNowForm;
        $this->serviceList->setColumns(array(
            'host_name',
            'host_output',
            'host_state',
            'service_output',
            'service_description',
            'service_state',
            'service_problem',
            'service_handled',
            'service_acknowledged',
            'service_in_downtime',
            'service_is_flapping',
            'service_notifications_enabled',
            'service_last_comment',
            'service_last_ack'
            /*,
            'service_passive_checks_enabled',
            'service_event_handler_enabled',
            'service_flap_detection_enabled',
            'service_active_checks_enabled',
            'service_obsessing'*/
        ));
        $unhandledObjects = $this->serviceList->getUnhandledObjects();
        $unhandledFilterExpressions = array();
        foreach ($unhandledObjects as $service) {
            $unhandledFilterExpressions[] = Filter::matchAll(
                Filter::where('host', $service->getHost()->getName()),
                Filter::where('service', $service->getName())
            );
        }

        $objectsInDowntime = $this->serviceList->getObjectsInDowntime();
        $downtimeFilterExpressions = array();
        foreach ($objectsInDowntime as $service) {
            $downtimeFilterExpressions[] = Filter::matchAll(
                Filter::where('downtime_host', $service->getHost()->getName()),
                Filter::where('downtime_service', $service->getName())
            );
        }

        $acknowledgedObjects = $this->serviceList->getAcknowledgedObjects();
        if (! empty($acknowledgedObjects)) {
            $removeAckForm = new RemoveAcknowledgementCommandForm();
            $removeAckForm
                ->setObjects($acknowledgedObjects)
                ->handleRequest();
            $this->view->removeAckForm = $removeAckForm;
        }
        /*
        if (! empty($objectsInDowntime)) {
            $removeDowntimeForm = new DeleteDowntimeCommandForm();
            $removeDowntimeForm
                ->setObjects($objectsInDowntime)
                ->handleRequest();
            $this->view->removeDowntimeForm = $removeDowntimeForm;
        }
        */
        $this->setAutorefreshInterval(15);
        $this->view->rescheduleAllLink = Url::fromRequest()->setPath('monitoring/services/reschedule-check');
        $this->view->downtimeAllLink = Url::fromRequest()->setPath('monitoring/services/schedule-downtime');
        $this->view->processCheckResultAllLink = Url::fromRequest()->setPath(
            'monitoring/services/process-check-result'
        );
        $this->view->addCommentLink = Url::fromRequest()->setPath('monitoring/services/add-comment');
        $this->view->deleteCommentLink = Url::fromRequest()->setPath('monitoring/services/delete-comment');
        $this->view->stats = $this->serviceList->getStateSummary();
        $this->view->objects = $this->serviceList;
        $this->view->unhandledObjects = $unhandledObjects;
        $unhandledFilterQueryString = Filter::matchAny($unhandledFilterExpressions)->toQueryString();
        $this->view->acknowledgeUnhandledLink = Url::fromPath('monitoring/services/acknowledge-problem')
            ->setQueryString($unhandledFilterQueryString);
        $this->view->downtimeUnhandledLink = Url::fromPath('monitoring/services/schedule-downtime')
            ->setQueryString($unhandledFilterQueryString);
        $this->view->acknowledgedObjects = $acknowledgedObjects;
        $this->view->objectsInDowntime = $objectsInDowntime;
        $this->view->inDowntimeLink = Url::fromPath('monitoring/list/downtimes')
            ->setQueryString(Filter::matchAny($downtimeFilterExpressions)->toQueryString());
        $this->view->commentsLink = Url::fromRequest()
            ->setPath('monitoring/list/comments');
    }

    /**
     * Add a service comment
     */
    public function addCommentAction()
    {
        $this->assertPermission('monitoring/command/comment/add');

        $form = new AddCommentCommandForm();
        $form->setTitle($this->translate('Add Service Comments'));
        $this->handleCommandForm($form);
    }


    /**
     * Delete a comment
     */
    public function deleteCommentAction()
    {
        $this->assertPermission('monitoring/command/comment/delete');

        $form = new DeleteCommentCommandForm();
        $form->setTitle($this->translate('Delete Service Comments'));
        $this->handleCommandForm($form);
    }


    /**
     * Acknowledge service problems
     */
    public function acknowledgeProblemAction()
    {
        $this->assertPermission('monitoring/command/acknowledge-problem');

        $form = new AcknowledgeProblemCommandForm();
        $form->setTitle($this->translate('Acknowledge Service Problems'));
        $this->handleCommandForm($form);
    }

    /**
     * Reschedule service checks
     */
    public function rescheduleCheckAction()
    {
        $this->assertPermission('monitoring/command/schedule-check');

        $form = new ScheduleServiceCheckCommandForm();
        $form->setTitle($this->translate('Reschedule Service Checks'));
        $this->handleCommandForm($form);
    }

    /**
     * Schedule service downtimes
     */
    public function scheduleDowntimeAction()
    {
        $this->assertPermission('monitoring/command/downtime/schedule');

        $form = new ScheduleServiceDowntimeCommandForm();
        $form->setTitle($this->translate('Schedule Service Downtimes'));
        $this->handleCommandForm($form);
    }

    /**
     * Submit passive service check results
     */
    public function processCheckResultAction()
    {
        $this->assertPermission('monitoring/command/process-check-result');

        $form = new ProcessCheckResultCommandForm();
        $form->setTitle($this->translate('Submit Passive Service Check Results'));
        $this->handleCommandForm($form);
    }
}
