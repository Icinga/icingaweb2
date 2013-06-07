<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Backend\Statusdat;

use Icinga\Backend\MonitoringObjectList as MList;
use Icinga\Protocol\Statusdat;
use Icinga\Backend\Statusdat\DataView\StatusdatServiceView as StatusdatServiceView;
use Icinga\Exception;

/**
 * Class ServicelistQuery
 * @package Icinga\Backend\Statusdat
 */
class ServicelistQuery extends Query
{
    /**
     * @var \Icinga\Protocol\Statusdat\Query
     */
    protected $query;

    /**
     * @var string
     */
    protected $view = 'Icinga\Backend\Statusdat\DataView\StatusdatServiceView';


    public function init()
    {
        $this->reader = $this->backend->getReader();
        $this->query = $this->reader->select()->from("services", array());
    }
}
