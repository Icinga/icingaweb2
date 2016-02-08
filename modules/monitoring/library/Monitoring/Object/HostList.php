<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterOr;
use Icinga\Data\SimpleQuery;
use Icinga\Util\StringHelper;

/**
 * A host list
 */
class HostList extends ObjectList
{
    protected $dataViewName = 'hoststatus';

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
     * @return  SimpleQuery
     */
    public function getStateSummary()
    {
        $hostStates = array_fill_keys(self::getHostStatesSummaryEmpty(), 0);
        foreach ($this as $host) {
            $unhandled = (bool) $host->problem === true && (bool) $host->handled === false;

            $stateName = 'hosts_' . $host::getStateText($host->state);
            ++$hostStates[$stateName];
            ++$hostStates[$stateName. ($unhandled ? '_unhandled' : '_handled')];
        }

        $hostStates['hosts_total'] = count($this);

        $ds = new ArrayDatasource(array((object) $hostStates));
        return $ds->select();
    }

    /**
     * Return an empty array with all possible host state names
     *
     * @return array    An array containing all possible host states as keys and 0 as values.
     */
    public static function getHostStatesSummaryEmpty()
    {
        return StringHelper::cartesianProduct(
            array(
                array('hosts'),
                array(
                    Host::getStateText(Host::STATE_UP),
                    Host::getStateText(Host::STATE_DOWN),
                    Host::getStateText(Host::STATE_UNREACHABLE),
                    Host::getStateText(Host::STATE_PENDING)
                ),
                array(null, 'handled', 'unhandled')
            ),
            '_'
        );
    }

    /**
     * Returns a Filter that matches all hosts in this list
     *
     * @return Filter
     */
    public function objectsFilter($columns = array('host' => 'host'))
    {
        $filterExpression = array();
        foreach ($this as $host) {
            $filterExpression[] = Filter::where($columns['host'], $host->getName());
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
            ->from('hostcomment', array('host_name'))
            ->applyFilter(clone $this->filter);
    }

    /**
     * Get the scheduled downtimes
     *
     * @return \Icinga\Module\Monitoring\DataView\Hostdowntime
     */
    public function getScheduledDowntimes()
    {
        return $this->backend
            ->select()
            ->from('hostdowntime', array('host_name'))
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
                (bool) $object->host_acknowledged === false) {
                $unhandledObjects[] = $object;
            }
        }
        return $this->newFromArray($unhandledObjects);
    }
}
