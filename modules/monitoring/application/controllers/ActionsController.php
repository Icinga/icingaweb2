<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimesCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleHostDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\ScheduleServiceDowntimeCommandForm;
use Icinga\Module\Monitoring\Object\HostList;
use Icinga\Module\Monitoring\Object\ServiceList;

/**
 * Monitoring API
 */
class Monitoring_ActionsController extends Controller
{
    /**
     * Schedule host downtimes
     */
    public function scheduleHostDowntimeAction()
    {
        // @TODO(el): Require a filter
        // @TODO(el): $this->backend->list('host')->handleRequest()->fetchAll()
        $hostList = new HostList($this->backend);
        $this->applyRestriction('monitoring/filter/objects', $hostList);
        $hostList->addFilter(Filter::fromQueryString((string) $this->params));
        if (! $hostList->count()) {
            // @TODO(el): Use ApiResponse class for unified response handling.
            $this->getResponse()->sendJson(array(
                'status'    => 'fail',
                'message'   => 'No hosts found matching the given filter'
            ));
        }
        $form = new ScheduleHostDowntimeCommandForm();
        $form
            ->setIsApiTarget(true)
            ->setObjects($hostList->fetch())
            ->handleRequest($this->getRequest());
    }

    /**
     * Remove host downtimes
     */
    public function removeHostDowntimeAction()
    {
        $downtimes = $this->backend
            ->select()
            ->from('downtime', array('host_name', 'id' => 'downtime_internal_id'))
            ->where('object_type', 'host')
            ->applyFilter($this->getRestriction('monitoring/filter/objects'))
            ->setRequiresFilter(true)
            ->handleRequest($this->getRequest())
            ->fetchAll();
        if (empty($downtimes)) {
            // @TODO(el): Use ApiResponse class for unified response handling.
            $this->getResponse()->sendJson(array(
                'status'    => 'fail',
                'message'   => 'No downtimes found matching the given filter'
            ));
        }
        $form = new DeleteDowntimesCommandForm();
        $form
            ->setIsApiTarget(true)
            ->setDowntimes($downtimes)
            ->handleRequest($this->getRequest());
        // @TODO(el): Respond w/ the downtimes deleted instead of the notifiaction added by
        // DeleteDowntimesCommandForm::onSuccess().
    }

    /**
     * Schedule service downtimes
     */
    public function scheduleServiceDowntimeAction()
    {
        // @TODO(el): Require a filter
        // @TODO(el): $this->backend->list('service')->handleRequest()->fetchAll()
        $serviceList = new ServiceList($this->backend);
        $this->applyRestriction('monitoring/filter/objects', $serviceList);
        $serviceList->addFilter(Filter::fromQueryString((string) $this->params));
        if (! $serviceList->count()) {
            // @TODO(el): Use ApiResponse class for unified response handling.
            $this->getResponse()->sendJson(array(
                'status'    => 'fail',
                'message'   => 'No services found matching the given filter'
            ));
        }
        $form = new ScheduleServiceDowntimeCommandForm();
        $form
            ->setIsApiTarget(true)
            ->setObjects($serviceList->fetch())
            ->handleRequest($this->getRequest());
    }

    /**
     * Remove service downtimes
     */
    public function removeServiceDowntimeAction()
    {
        $downtimes = $this->backend
            ->select()
            ->from('downtime', array('host_name', 'service_description', 'id' => 'downtime_internal_id'))
            ->where('object_type', 'service')
            ->applyFilter($this->getRestriction('monitoring/filter/objects'))
            ->setRequiresFilter(true)
            ->handleRequest($this->getRequest())
            ->fetchAll();
        if (empty($downtimes)) {
            // @TODO(el): Use ApiResponse class for unified response handling.
            $this->getResponse()->sendJson(array(
                'status'    => 'fail',
                'message'   => 'No downtimes found matching the given filter'
            ));
        }
        $form = new DeleteDowntimesCommandForm();
        $form
            ->setIsApiTarget(true)
            ->setDowntimes($downtimes)
            ->handleRequest($this->getRequest());
        // @TODO(el): Respond w/ the downtimes deleted instead of the notifiaction added by
        // DeleteDowntimesCommandForm::onSuccess().
    }
}
