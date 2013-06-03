<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 1/29/13
 * Time: 11:36 AM
 * To change this template use File | Settings | File Templates.
 */
namespace Icinga\Backend\Statusdat;
use Icinga\Protocol\Statusdat;
use Icinga\Exception;


class HostListQuery extends Query
{
    /**
     * @var \Icinga\Protocol\Statusdat\Query
     */
    protected $query;
    protected $view = 'Icinga\Backend\Statusdat\DataView\StatusdatHostView';

    public function init() {
        $this->reader = $this->backend->getReader();
        $this->query = $this->reader->select()->from("hosts",array());
    }



}
