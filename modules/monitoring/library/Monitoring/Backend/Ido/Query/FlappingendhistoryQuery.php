<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service flapping end history records
 */
class FlappingendhistoryQuery extends FlappingstarthistoryQuery
{
    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $columns = array_keys(
            $this->columnMap['flappinghistory'] + $this->columnMap['hosts']
        );
        foreach ($this->columnMap['services'] as $column => $_) {
            $columns[$column] = new Zend_Db_Expr('NULL');
        }
        if ($this->fetchHistoryColumns) {
            $columns = array_merge($columns, array_keys($this->columnMap['history']));
        }
        $hosts = $this->createSubQuery('Hostflappingendhistory', $columns);
        $this->subQueries[] = $hosts;
        $this->flappingStartHistoryQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $columns = array_keys(
            $this->columnMap['flappinghistory'] + $this->columnMap['hosts'] + $this->columnMap['services']
        );
        if ($this->fetchHistoryColumns) {
            $columns = array_merge($columns, array_keys($this->columnMap['history']));
        }
        $services = $this->createSubQuery('Serviceflappingendhistory', $columns);
        $this->subQueries[] = $services;
        $this->flappingStartHistoryQuery->union(array($services), Zend_Db_Select::SQL_UNION_ALL);
    }
}
