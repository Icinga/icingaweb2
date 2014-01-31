<?php

namespace Icinga\Module\Monitoring\Object;

use Icinga\Module\Monitoring\DataView\ServiceStatus;

class Service extends AbstractObject
{

    public $type        = self::TYPE_SERVICE;
    public $prefix      = 'service_';
    private $view       = null;

    public function populate()
    {
        $this->fetchComments()
            ->fetchDowntimes()
            ->fetchHostgroups()
            ->fetchServicegroups()
            ->fetchContacts()
            ->fetchContactGroups()
            ->fetchCustomvars();
    }

    protected function getProperties()
    {
        $this->view = ServiceStatus::fromRequest($this->getRequest());
        return $this->view->getQuery()->fetchRow();
    }
}
