<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

/**
 * A service list
 */
class ServiceList extends ObjectList
{
    protected $dataViewName = 'serviceStatus';

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
     * @return object   The summary
     */
    public function getStateSummary()
    {
        $serviceStates = $this->prepareStateNames('services_', array(
            Service::getStateText(Service::STATE_OK),
            Service::getStateText(Service::STATE_WARNING),
            Service::getStateText(Service::STATE_CRITICAL),
            Service::getStateText(Service::STATE_UNKNOWN),
            Service::getStateText(Service::STATE_PENDING),
        ));

        $hostStates = $this->prepareStateNames('hosts_', array(
            Host::getStateText(Host::STATE_UP),
            Host::getStateText(Host::STATE_DOWN),
            Host::getStateText(Host::STATE_UNREACHABLE),
            Host::getStateText(Host::STATE_PENDING),
        ));

        foreach ($this as $service) {
            $unhandled = false;
            if ((bool) $service->problem === true && (bool) $service->handled === false) {
                $unhandled = true;
            }

            $stateName = 'services_' . $service::getStateText($service->state);
            ++$serviceStates[$stateName];
            ++$serviceStates[$stateName . ($unhandled ? '_unhandled' : '_handled')];
            if (! isset($knownHostStates[$service->getHost()->getName()])) {
                $knownHostStates[$service->getHost()->getName()] = true;
                ++$hostStates['hosts_' . $service->getHost()->getStateText($service->host_state)];
            }
        }

        $serviceStates['services_total'] = count($this);

        return (object)$serviceStates;
    }
}
