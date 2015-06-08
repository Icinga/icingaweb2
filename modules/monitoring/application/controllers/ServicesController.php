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
use Icinga\Module\Monitoring\Forms\Command\Object\SendCustomNotificationCommandForm;
use Icinga\Module\Monitoring\Object\ServiceList;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;

class Monitoring_ServicesController extends Controller
{
    /**
     * @var ServiceList
     */
    protected $serviceList;

    public function init()
    {
        $serviceList = new ServiceList($this->backend);
        $serviceList->setFilter(Filter::fromQueryString(
            (string) $this->params->without(array('service_problem', 'service_handled', 'view'))
        ));
        $this->serviceList = $serviceList;
        $this->view->listAllLink = Url::fromRequest()->setPath('monitoring/list/services');

        $this->getTabs()->add(
            'show',
            array(
                'title' => sprintf(
                    $this->translate('Show summarized information for %u services'),
                    count($this->serviceList)
                ),
                'label' => $this->translate('Services') . sprintf(' (%d)', count($this->serviceList)),
                'url'   => Url::fromRequest(),
                'icon'  => 'services'
            )
        )->extend(new DashboardAction())->activate('show');
    }

    protected function handleCommandForm(ObjectsCommandForm $form)
    {
        $this->serviceList->setColumns(array(
            'host_icon_image',
            'host_icon_image_alt',
            'host_name',
            'host_address',
            'host_output',
            'host_state',
            'host_problem',
            'host_handled',
            'service_icon_image',
            'service_icon_image_alt',
            'service_description',
            'service_state',
            'service_problem',
            'service_handled',
            'service_acknowledged',
            'service_in_downtime',
            'service_is_flapping',
            'service_output',
            'service_last_ack',
            'service_last_comment',
            'service_notifications_enabled',
            'service_active_checks_enabled',
            'service_passive_checks_enabled'
        ));

        $form
            ->setObjects($this->serviceList)
            ->setRedirectUrl(Url::fromPath('monitoring/services/show')->setParams($this->params))
            ->handleRequest();

        $this->view->form = $form;
        $this->view->objects = $this->serviceList;
        $this->view->stats = $this->serviceList->getServiceStateSummary();
        $this->view->serviceStates = true;
        $this->view->hostStates = $this->serviceList->getHostStateSummary();
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
            'host_icon_image',
            'host_icon_image_alt',
            'host_name',
            'host_address',
            'host_output',
            'host_state',
            'host_problem',
            'host_handled',
            'service_icon_image',
            'service_icon_image_alt',
            'service_output',
            'service_description',
            'service_state',
            'service_problem',
            'service_handled',
            'service_acknowledged',
            'service_in_downtime',
            'service_is_flapping',
            'service_last_comment',
            'service_last_ack',
            'service_notifications_enabled',
            'service_active_checks_enabled',
            'service_passive_checks_enabled'
            /*
            'service_event_handler_enabled',
            'service_flap_detection_enabled',
            'service_obsessing'*/
        ));

        $acknowledgedObjects = $this->serviceList->getAcknowledgedObjects();
        if (! empty($acknowledgedObjects)) {
            $removeAckForm = new RemoveAcknowledgementCommandForm();
            $removeAckForm
                ->setObjects($acknowledgedObjects)
                ->handleRequest();
            $this->view->removeAckForm = $removeAckForm;
        }

        $this->setAutorefreshInterval(15);
        $this->view->rescheduleAllLink = Url::fromRequest()->setPath('monitoring/services/reschedule-check');
        $this->view->downtimeAllLink = Url::fromRequest()->setPath('monitoring/services/schedule-downtime');
        $this->view->processCheckResultAllLink = Url::fromRequest()->setPath(
            'monitoring/services/process-check-result'
        );
        $this->view->addCommentLink = Url::fromRequest()->setPath('monitoring/services/add-comment');
        $this->view->deleteCommentLink = Url::fromRequest()->setPath('monitoring/services/delete-comment');
        $this->view->stats = $this->serviceList->getServiceStateSummary();
        $this->view->hostStats = $this->serviceList->getHostStateSummary();
        $this->view->objects = $this->serviceList;
        $this->view->unhandledObjects = $this->serviceList->getUnhandledObjects();
        $this->view->problemObjects = $this->serviceList->getProblemObjects();
        $this->view->downtimeUnhandledLink = Url::fromPath('monitoring/services/schedule-downtime')
            ->setQueryString($this->serviceList->getUnhandledObjects()->objectsFilter()->toQueryString());
        $this->view->downtimeLink = Url::fromPath('monitoring/services/schedule-downtime')
            ->setQueryString($this->serviceList->getProblemObjects()->objectsFilter()->toQueryString());
        $this->view->acknowledgedObjects = $acknowledgedObjects;
        $this->view->acknowledgeLink = Url::fromPath('monitoring/services/acknowledge-problem')
            ->setQueryString($this->serviceList->getUnacknowledgedObjects()->objectsFilter()->toQueryString());
        $this->view->unacknowledgedObjects = $this->serviceList->getUnacknowledgedObjects();
        $this->view->objectsInDowntime = $this->serviceList->getObjectsInDowntime();
        $this->view->inDowntimeLink = Url::fromPath('monitoring/list/services')
            ->setQueryString($this->serviceList->getObjectsInDowntime()
            ->objectsFilter(array('host' => 'host_name', 'service' => 'service_description'))->toQueryString());
        $this->view->showDowntimesLink = Url::fromPath('monitoring/downtimes/show')
            ->setQueryString(
                $this->serviceList->getObjectsInDowntime()
                    ->objectsFilter()->toQueryString()
            );
        $this->view->commentsLink = Url::fromRequest()
            ->setPath('monitoring/list/comments');
        $this->view->baseFilter = $this->serviceList->getFilter();
        $this->view->sendCustomNotificationLink = Url::fromRequest()->setPath(
            'monitoring/services/send-custom-notification'
        );
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

    /**
     * Send a custom notification for services
     */
    public function sendCustomNotificationAction()
    {
        $this->assertPermission('monitoring/command/send-custom-notification');

        $form = new SendCustomNotificationCommandForm();
        $form->setTitle($this->translate('Send Custom Service Notification'));
        $this->handleCommandForm($form);
    }
}
