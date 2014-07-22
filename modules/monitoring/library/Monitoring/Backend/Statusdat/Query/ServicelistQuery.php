<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

use Icinga\Protocol\Statusdat;
use Icinga\Exception;

/**
 * Class ServicelistQuery
 * @package Icinga\Backend\Statusdat
 */
class ServicelistQuery extends StatusdatQuery
{
    /**
     * @var \Icinga\Protocol\Statusdat\Query
     */
    protected $query;

    /**
     * @var string
     */
    protected $view = 'Icinga\Backend\Statusdat\DataView\StatusdatServiceView';

    public function selectBase()
    {
        $this->select()->from("services", array());
    }
}
