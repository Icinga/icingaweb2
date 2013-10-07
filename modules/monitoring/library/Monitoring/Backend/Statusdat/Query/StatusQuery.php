<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 7/17/13
 * Time: 1:29 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

use Icinga\Protocol\Statusdat;
use Icinga\Exception;

class StatusQuery extends Query
{

    private function getTarget()
    {
        foreach($this->getColumns() as $column) {
            if(preg_match("/^service/",$column))
                return "service";
        }
        return "host";
    }

    public function init()
    {
        $target = $this->getTarget();
        $this->reader = $this->ds;
        $this->setResultViewClass(ucfirst($target)."StatusView");
        $this->setBaseQuery($this->reader->select()->from($target."s", array()));

    }

}
