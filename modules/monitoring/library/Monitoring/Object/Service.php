<?php

namespace Icinga\Module\Monitoring\Object;

use Icinga\Module\Monitoring\DataView\ServiceStatus;
use Icinga\Data\Db\Query;

class Service extends AbstractObject
{

    public $type        = self::TYPE_SERVICE;
    public $prefix      = 'service_';
    private $view       = null;

    protected function applyObjectFilter(Query $query)
    {
        return $query->where('service_host_name', $this->host_name)
                     ->where('service_description', $this->service_description);
    }

    public function populate()
    {
        $this->fetchComments()
            ->fetchServicegroups()
            ->fetchContacts()
            ->fetchContactGroups()
            ->fetchCustomvars()
            ->fetchDowntimes();
    }

    protected function getProperties()
    {
        $this->view = ServiceStatus::fromRequest($this->getRequest());
        return $this->view->getQuery()->fetchRow();
    }
}
