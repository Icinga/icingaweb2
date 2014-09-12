<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Form\Command\Object\AcknowledgeProblemCommandForm;
use Icinga\Module\Monitoring\Form\Command\Object\AddCommentCommandForm;
use Icinga\Module\Monitoring\Form\Command\Object\CheckNowCommandForm;
use Icinga\Module\Monitoring\Form\Command\Object\DeleteCommentCommandForm;
use Icinga\Module\Monitoring\Form\Command\Object\DeleteDowntimeCommandForm;
use Icinga\Module\Monitoring\Form\Command\Object\ObjectsCommandForm;
use Icinga\Module\Monitoring\Form\Command\Object\RemoveAcknowledgementCommandForm;
use Icinga\Module\Monitoring\Form\Command\Object\ScheduleServiceCheckCommandForm;
use Icinga\Module\Monitoring\Form\Command\Object\ScheduleServiceDowntimeCommandForm;
use Icinga\Module\Monitoring\Form\Command\Object\ToggleObjectFeaturesCommandForm;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Web\Url;

class Monitoring_ServiceController extends Controller
{
    /**
     * @var Service
     */
    protected $service;

    public function moduleInit()
    {
        parent::moduleInit();
        $service = new Service($this->backend, $this->params->get('host'), $this->params->get('service'));
        if ($service->fetch() === false) {
            throw new Zend_Controller_Action_Exception($this->translate('Service not found'));
        }
        $this->service = $service;
    }

    public function showAction()
    {
        $this->setAutorefreshInterval(10);
        $checkNowForm = new CheckNowCommandForm();
        $checkNowForm
            ->setObjects($this->service)
            ->handleRequest();
        $this->view->checkNowForm = $checkNowForm;

        if ( ! in_array((int) $this->service->state, array(0, 99))) {
            if ((bool) $this->service->acknowledged) {
                $removeAckForm = new RemoveAcknowledgementCommandForm();
                $removeAckForm
                    ->setObjects($this->service)
                    ->handleRequest();
                $this->view->removeAckForm = $removeAckForm;
            } else {
                $ackForm = new AcknowledgeProblemCommandForm();
                $ackForm
                    ->setObjects($this->service)
                    ->handleRequest();
                $this->view->ackForm = $ackForm;
            }
        }
        if (count($this->service->comments) > 0) {
            $delCommentForm = new DeleteCommentCommandForm();
            $delCommentForm
                ->setObjects($this->service)
                ->handleRequest();
            $this->view->delCommentForm = $delCommentForm;
        }

        if (count($this->service->downtimes > 0)) {
            $delDowntimeForm = new DeleteDowntimeCommandForm();
            $delDowntimeForm
                ->setObjects($this->service)
                ->handleRequest();
            $this->view->delDowntimeForm = $delDowntimeForm;
        }

        $toggleFeaturesForm = new ToggleObjectFeaturesCommandForm();
        $toggleFeaturesForm
            ->load($this->service)
            ->setObjects($this->service)
            ->handleRequest();
        $this->view->toggleFeaturesForm = $toggleFeaturesForm;

        $this->view->object = $this->service->populate();
    }

    protected function handleCommandForm(ObjectsCommandForm $form)
    {
        $form
            ->setObjects($this->service)
            ->setRedirectUrl(Url::fromPath(
                'monitoring/service/show',
                array('host' => $this->service->getHost(), 'service' => $this->service->getService())
            ))
            ->handleRequest();
        $this->view->form = $form;
        $this->_helper->viewRenderer('command');
        return $form;
    }

    /**
     * Acknowledge a service downtime
     */
    public function acknowledgeProblemAction()
    {
        $this->view->title = $this->translate('Acknowledge Service Downtime');
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
}
