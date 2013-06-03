<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 1/29/13
 * Time: 11:36 AM
 * To change this template use File | Settings | File Templates.
 */
namespace Icinga\Backend\Statusdat;
use Icinga\Backend\MonitoringObjectList as MList;
use Icinga\Protocol\Statusdat;
use Icinga\Backend\Statusdat\DataView\StatusdatServiceView as StatusdatServiceView;
use Icinga\Exception;


class ServicelistQuery extends Query
{
    /**
     * @var \Icinga\Protocol\Statusdat\Query
     */
    protected $query;
    protected $view = 'Icinga\Backend\Statusdat\DataView\StatusdatServiceView';


    public function init() {
        $this->reader = $this->backend->getReader();
        $this->query = $this->reader->select()->from("services",array());
    }



}
