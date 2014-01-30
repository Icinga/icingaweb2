<?php

namespace Icinga\Module\Monitoring\Object;

use Icinga\Module\Monitoring\DataView\HostStatus;

class Host extends AbstractObject
{

    public $type        = self::TYPE_HOST;
    public $prefix      = 'host_';
    private $view       = null;


    public function populate()
    {
        $this->fetchComments()
            ->fetchDowntimes()
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
