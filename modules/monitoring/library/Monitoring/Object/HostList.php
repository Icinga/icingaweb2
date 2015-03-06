<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

/**
 * A host list
 */
class HostList extends ObjectList
{
    protected $dataViewName = 'hostStatus';

    protected $columns = array('host_name');

    protected function fetchObjects()
    {
        $hosts = array();
        $query = $this->backend->select()->from($this->dataViewName, $this->columns)->applyFilter($this->filter)
            ->getQuery()->getSelectQuery()->query();
        foreach ($query as $row) {
            /** @var object $row */
            $host = new Host($this->backend, $row->host_name);
            $host->setProperties($row);
            $hosts[] = $host;
        }
        return $hosts;
    }

    /**
     * Create a state summary of all hosts that can be consumed by hostssummary.phtml
     *
     * @return object   The summary
     */
    public function getStateSummary()
    {
        $hostStates = $this->prepareStateNames('hosts_', array(
            Host::getStateText(Host::STATE_UP),
            Host::getStateText(Host::STATE_DOWN),
            Host::getStateText(Host::STATE_UNREACHABLE),
            Host::getStateText(Host::STATE_PENDING)
        ));

        foreach ($this as $host) {
            $unhandled = (bool) $host->problem === true && (bool) $host->handled === false;

            $stateName = 'hosts_' . $host::getStateText($host->state);
            ++$hostStates[$stateName];
            ++$hostStates[$stateName. ($unhandled ? '_unhandled' : '_handled')];
        }

        $hostStates['hosts_total'] = count($this);

        return (object)$hostStates;
    }
}
