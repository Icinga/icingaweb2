<?php

namespace Icinga\Module\Monitoring\Object;

use Icinga\Data\BaseQuery as Query;
use Icinga\Module\Monitoring\DataView\HostStatus;

class Host extends AbstractObject
{

    public $type        = self::TYPE_HOST;
    public $prefix      = 'host_';
    private $view   = null;


    public function populate()
    {
        $this->fetchComments()
            ->fetchDowntimes()
            ->fetchHostgroups()
            ->fetchContacts()
            ->fetchContactGroups();
    }

    protected function getProperties()
    {
        $this->view = HostStatus::fromRequest($this->getRequest());
        return $this->view->getQuery()->fetchRow();
    }
}
