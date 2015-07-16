<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterEqual;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\CheckNowCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ObjectsCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ProcessCheckResultCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\RemoveAcknowledgementCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostCheckCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\SendCustomNotificationCommandForm;
use Icinga\Module\Monitoring\Object\HostList;
use Icinga\Web\Url;
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
        $hostList->setFilter(Filter::fromQueryString((string) $this->params));
        $this->applyRestriction('monitoring/filter/objects', $hostList);
        $this->hostList = $hostList;
        $this->getTabs()->add(
            'show',
            array(
                'title' => sprintf(
                    $this->translate('Show summarized information for %u hosts'),
                    count($this->hostList)
                ),
                'label' => $this->translate('Hosts') . sprintf(' (%d)', count($this->hostList)),
                'url'   => Url::fromRequest(),
                'icon'  => 'host'
            )
        )->extend(new DashboardAction())->activate('show');
        $this->view->listAllLink = Url::fromRequest()->setPath('monitoring/list/hosts');
    }

    protected function handleCommandForm(ObjectsCommandForm $form)
    {
        $this->hostList->setColumns(array(
            'host_icon_image',
            'host_icon_image_alt',
            'host_name',
            'host_address',
            'host_address6',
            'host_state',
            'host_problem',
            'host_handled',
            'host_acknowledged',
            'host_in_downtime',
            'host_last_ack',
            'host_is_flapping',
            'host_last_comment',
            'host_output',
            'host_notifications_enabled',
            'host_active_checks_enabled',
            'host_passive_checks_enabled'
        ));

        $form
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
        $checkNowForm = new CheckNowCommandForm();
        $checkNowForm
            ->setObjects($this->hostList)
            ->handleRequest();
        $this->view->checkNowForm = $checkNowForm;
        $this->hostList->setColumns(array(
            'host_icon_image',
            'host_icon_image_alt',
            'host_name',
            'host_address',
            'host_address6',
            'host_state',
            'host_problem',
            'host_handled',
            'host_acknowledged',
            'host_in_downtime',
            'host_last_ack',
            'host_is_flapping',
            'host_last_comment',
            'host_output',
            'host_notifications_enabled',
            'host_active_checks_enabled',
            'host_passive_checks_enabled'
            /*'host_event_handler_enabled',
            'host_flap_detection_enabled',
            'host_obsessing'*/
        ));

        $acknowledgedObjects = $this->hostList->getAcknowledgedObjects();
        if (! empty($acknowledgedObjects)) {
            $removeAckForm = new RemoveAcknowledgementCommandForm();
            $removeAckForm
                ->setObjects($acknowledgedObjects)
                ->handleRequest();
            $this->view->removeAckForm = $removeAckForm;
        }

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
                    ->andFilter(FilterEqual::where('downtime_objecttype', 'host'))
                    ->toQueryString()
            );
        $this->view->commentsLink = Url::fromRequest()->setPath('monitoring/list/comments');
        $this->view->baseFilter = $this->hostList->getFilter();
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
     * Delete a comment
     */
    public function deleteCommentAction()
    {
        $this->assertPermission('monitoring/command/comment/delete');

        $form = new DeleteCommentCommandForm();
        $form->setTitle($this->translate('Delete Host Comments'));
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
