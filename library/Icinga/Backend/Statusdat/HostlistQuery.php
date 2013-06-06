<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Backend\Statusdat;

use Icinga\Protocol\Statusdat;
use Icinga\Exception;

/**
 * Class HostListQuery
 * @package Icinga\Backend\Statusdat
 */
class HostListQuery extends Query
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
    public function init()
    {
        $this->reader = $this->backend->getReader();
        $this->query = $this->reader->select()->from("hosts", array());
    }
}
