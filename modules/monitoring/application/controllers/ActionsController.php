<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

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
     * Get the filter from URL parameters or exit immediately if the filter is empty
     *
     * @return Filter
     */
    protected function getFilterOrExitIfEmpty()
    {
        $filter = Filter::fromQueryString((string) $this->params);
        if ($filter->isEmpty()) {
            $this->getResponse()->json()
                ->setFailData(array('filter' => 'Filter is required and must not be empty'))
                ->sendResponse();
        }
        return $filter;
    }

    /**
     * Schedule host downtimes
     */
    public function scheduleHostDowntimeAction()
    {
        $filter = $this->getFilterOrExitIfEmpty();
        $hostList = new HostList($this->backend);
        $hostList
            ->applyFilter($this->getRestriction('monitoring/filter/objects'))
            ->applyFilter($filter);
        if (! $hostList->count()) {
            $this->getResponse()->json()
                ->setFailData(array('filter' => 'No hosts found matching the filter'))
                ->sendResponse();
        }
        $form = new ScheduleHostDowntimeCommandForm();
        $form
            ->setIsApiTarget(true)
            ->setBackend($this->backend)
            ->setObjects($hostList->fetch())
            ->handleRequest($this->getRequest());
    }

    /**
     * Remove host downtimes
     */
    public function removeHostDowntimeAction()
    {
        $filter = $this->getFilterOrExitIfEmpty();
        $downtimes = $this->backend
            ->select()
            ->from('downtime', array('host_name', 'id' => 'downtime_internal_id', 'name' => 'downtime_name'))
            ->where('object_type', 'host')
            ->applyFilter($this->getRestriction('monitoring/filter/objects'))
            ->applyFilter($filter);
        if (! $downtimes->count()) {
            $this->getResponse()->json()
                ->setFailData(array('filter' => 'No downtimes found matching the filter'))
                ->sendResponse();
        }
        $form = new DeleteDowntimesCommandForm();
        $form
            ->setIsApiTarget(true)
            ->setDowntimes($downtimes->fetchAll())
            ->handleRequest($this->getRequest());
        // @TODO(el): Respond w/ the downtimes deleted instead of the notifiaction added by
        // DeleteDowntimesCommandForm::onSuccess().
    }

    /**
     * Schedule service downtimes
     */
    public function scheduleServiceDowntimeAction()
    {
        $filter = $this->getFilterOrExitIfEmpty();
        $serviceList = new ServiceList($this->backend);
        $serviceList
            ->applyFilter($this->getRestriction('monitoring/filter/objects'))
            ->applyFilter($filter);
        if (! $serviceList->count()) {
            $this->getResponse()->json()
                ->setFailData(array('filter' => 'No services found matching the filter'))
                ->sendResponse();
        }
        $form = new ScheduleServiceDowntimeCommandForm();
        $form
            ->setIsApiTarget(true)
            ->setBackend($this->backend)
            ->setObjects($serviceList->fetch())
            ->handleRequest($this->getRequest());
    }

    /**
     * Remove service downtimes
     */
    public function removeServiceDowntimeAction()
    {
        $filter = $this->getFilterOrExitIfEmpty();
        $downtimes = $this->backend
            ->select()
            ->from(
                'downtime',
                array('host_name', 'service_description', 'id' => 'downtime_internal_id', 'name' => 'downtime_name')
            )
            ->where('object_type', 'service')
            ->applyFilter($this->getRestriction('monitoring/filter/objects'))
            ->applyFilter($filter);
        if (! $downtimes->count()) {
            $this->getResponse()->json()
                ->setFailData(array('filter' => 'No downtimes found matching the filter'))
                ->sendResponse();
        }
        $form = new DeleteDowntimesCommandForm();
        $form
            ->setIsApiTarget(true)
            ->setDowntimes($downtimes->fetchAll())
            ->handleRequest($this->getRequest());
        // @TODO(el): Respond w/ the downtimes deleted instead of the notifiaction added by
        // DeleteDowntimesCommandForm::onSuccess().
    }
}
