<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Form\Command\CommandForm;
use Icinga\Module\Monitoring\Form\Command\Service\ScheduleServiceDowntimeCommandForm;
use Icinga\Module\Monitoring\Object\Service;

class Monitoring_ServiceController extends Controller
{
    /**
     * @var Service
     */
    protected $service;

    public function init()
    {
        $this->service = new Service($this->params);  // Use $this->_request->getParams() instead of $this->params
                                                      // once #7049 has been fixed
    }

    protected function handleCommandForm(CommandForm $form)
    {
        $form
            ->setService($this->service)
            ->handleRequest();
        $this->view->form = $form;
        $this->_helper->viewRenderer('command');
        return $form;
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
