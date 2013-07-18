<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 7/17/13
 * Time: 1:29 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Monitoring\Backend\Statusdat\Query;

use Icinga\Protocol\Statusdat;
use Icinga\Exception;


class StatusQuery extends Query
{
    /**
     * @var \Icinga\Protocol\Statusdat\Query
     */
    protected $query;

    /**
     * @var string
     */
    protected $view = 'Monitoring\Backend\Statusdat\DataView\StatusdatHostView';


    public function init()
    {

        $this->reader = $this->ds->getReader();
        $this->query = $this->reader->select()->from("hosts", array());
    }

}