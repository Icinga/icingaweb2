<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

use Icinga\Protocol\Statusdat;
use Icinga\Exception;

/**
 * Class HostListQuery
 * @package Icinga\Backend\Statusdat
 */
class HostListQuery extends StatusdatQuery
{
    /**
     * @var \Icinga\Protocol\Statusdat\Query
     */
    protected $query;

    /**
     * @var string
     */
    protected $view = 'Icinga\Backend\Statusdat\DataView\StatusdatHostView';

    /**
     * @return mixed|void
     */
    public function selectBase()
    {
        $this->select()->from("hosts", array());
    }
}
