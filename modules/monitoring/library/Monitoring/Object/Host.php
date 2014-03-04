<?php

namespace Icinga\Module\Monitoring\Object;

use Icinga\Module\Monitoring\DataView\HostStatus;
use Icinga\Data\Db\Query;

class Host extends AbstractObject
{

    public $type        = self::TYPE_HOST;
    public $prefix      = 'host_';
    private $view       = null;

    protected function applyObjectFilter(Query $query)
    {
        return $query->where('host_name', $this->host_name);
    }

    public function populate()
    {
        $this->fetchComments()
            ->fetchHostgroups()
            ->fetchContacts()
            ->fetchContactGroups()
            ->fetchCustomvars();
    }

    protected function getProperties()
    {
        $this->view = HostStatus::fromRequest($this->getRequest());
        return $this->view->getQuery()->fetchRow();
    }
}
