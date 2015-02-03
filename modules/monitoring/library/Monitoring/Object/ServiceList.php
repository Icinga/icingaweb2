<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
}
