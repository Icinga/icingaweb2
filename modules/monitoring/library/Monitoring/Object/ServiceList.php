<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterOr;
use Icinga\Data\SimpleQuery;
use Icinga\Util\StringHelper;

/**
 * A service list
 */
class ServiceList extends ObjectList
{
    protected $hostStateSummary;

    protected $serviceStateSummary;

    protected $dataViewName = 'servicestatus';

    protected $columns = array('host_name', 'service_description');

    protected function fetchObjects()
    {
        $services = array();
        $query = $this->backend->select()->from($this->dataViewName, $this->columns)->applyFilter($this->filter)
            ->getQuery()->getSelectQuery()->query();
        foreach ($query as $row) {
            /** @var object $row */
            $service = new Service($this->backend, $row->host_name, $row->service_description);
            $service->setProperties($row);
            $services[] = $service;
        }
        return $services;
    }

    /**
     * Create a state summary of all services that can be consumed by servicesummary.phtml
     *
     * @return  SimpleQuery
     */
    public function getServiceStateSummary()
    {
        if (! $this->serviceStateSummary) {
            $this->initStateSummaries();
        }

        $ds = new ArrayDatasource(array((object) $this->serviceStateSummary));
        return $ds->select();
    }

    /**
     * Create a state summary of all hosts that can be consumed by hostsummary.phtml
     *
     * @return  SimpleQuery
     */
    public function getHostStateSummary()
    {
        if (! $this->hostStateSummary) {
            $this->initStateSummaries();
        }

        $ds = new ArrayDatasource(array((object) $this->hostStateSummary));
        return $ds->select();
    }

    /**
     * Calculate the current state summary and populate hostStateSummary and serviceStateSummary
     * properties
     */
    protected function initStateSummaries()
    {
        $serviceStates = array_fill_keys(self::getServiceStatesSummaryEmpty(), 0);
        $hostStates = array_fill_keys(HostList::getHostStatesSummaryEmpty(), 0);

        foreach ($this as $service) {
            $unhandled = false;
            if ((bool) $service->problem === true && (bool) $service->handled === false) {
                $unhandled = true;
            }

            $stateName = 'services_' . $service::getStateText($service->state);
            ++$serviceStates[$stateName];
            ++$serviceStates[$stateName . ($unhandled ? '_unhandled' : '_handled')];

            if (! isset($knownHostStates[$service->getHost()->getName()])) {
                $unhandledHost = (bool) $service->host_problem === true && (bool) $service->host_handled === false;
                ++$hostStates['hosts_' . $service->getHost()->getStateText($service->host_state)];
                ++$hostStates['hosts_' . $service->getHost()->getStateText($service->host_state)
                        . ($unhandledHost ? '_unhandled' : '_handled')];
                $knownHostStates[$service->getHost()->getName()] = true;
            }
        }

        $serviceStates['services_total'] = count($this);
        $this->hostStateSummary = $hostStates;
        $this->serviceStateSummary = $serviceStates;
    }

    /**
     * Return an empty array with all possible host state names
     *
     * @return array    An array containing all possible host states as keys and 0 as values.
     */
    public static function getServiceStatesSummaryEmpty()
    {
        return StringHelper::cartesianProduct(
            array(
                array('services'),
                array(
                    Service::getStateText(Service::STATE_OK),
                    Service::getStateText(Service::STATE_WARNING),
                    Service::getStateText(Service::STATE_CRITICAL),
                    Service::getStateText(Service::STATE_UNKNOWN),
                    Service::getStateText(Service::STATE_PENDING)
                ),
                array(null, 'handled', 'unhandled')
            ),
            '_'
        );
    }

    /**
     * Returns a Filter that matches all hosts in this HostList
     *
     * @param   array   $columns    Override filter column names
     *
     * @return  Filter
     */
    public function objectsFilter($columns = array('host' => 'host', 'service' => 'service'))
    {
        $filterExpression = array();
        foreach ($this as $service) {
            $filterExpression[] = Filter::matchAll(
                Filter::where($columns['host'], $service->getHost()->getName()),
                Filter::where($columns['service'], $service->getName())
            );
        }
        return FilterOr::matchAny($filterExpression);
    }

    /**
     * Get the comments
     *
     * @return \Icinga\Module\Monitoring\DataView\Hostcomment
     */
    public function getComments()
    {
        return $this->backend
            ->select()
            ->from('servicecomment', array('host_name', 'service_description'))
            ->applyFilter(clone $this->filter);
    }

    /**
     * Get the scheduled downtimes
     *
     * @return \Icinga\Module\Monitoring\DataView\Servicedowntime
     */
    public function getScheduledDowntimes()
    {
        return $this->backend
            ->select()
            ->from('servicedowntime', array('host_name', 'service_description'))
            ->applyFilter(clone $this->filter);
    }

    /**
     * @return ObjectList
     */
    public function getUnacknowledgedObjects()
    {
        $unhandledObjects = array();
        foreach ($this as $object) {
            if (! in_array((int) $object->state, array(0, 99)) &&
                  (bool) $object->service_acknowledged === false) {
                $unhandledObjects[] = $object;
            }
        }
        return $this->newFromArray($unhandledObjects);
    }
}
