<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
}
