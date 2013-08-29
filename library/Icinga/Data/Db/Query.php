<?php

namespace Icinga\Data\Db;

use Icinga\Data\AbstractQuery;

class Query extends AbstractQuery
{
    /**
     * Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Base Query will be prepared here, has tables and cols
     * shared by full & count query
     */
    protected $baseQuery;

    /**
     * Select object
     */
    protected $selectQuery;

    /**
     * Select object used for count query
     */
    protected $countQuery;

    /**
     * Allow to override COUNT(*)
     */
    protected $countColumns;

    protected function init()
    {
        $this->db = $this->ds->getConnection()->getDb();
        $this->baseQuery = $this->db->select();
    }

    protected function getSelectQuery()
    {
        if ($this->selectQuery === null) {
            $this->createQueryObjects();
        }

        if ($this->hasLimit()) {
            $this->selectQuery->limit($this->getLimit(), $this->getOffset());
        }
        return $this->selectQuery;
    }
    
    protected function getCountQuery()
    {
        if ($this->countQuery === null) {
            $this->createQueryObjects();
        }
        return $this->countQuery;
    }

    protected function createQueryObjects()
    {
        $this->beforeCreatingCountQuery();
        $this->beforeCreatingSelectQuery();
        $this->selectQuery = clone($this->baseQuery);
        $this->selectQuery->columns($this->columns);
        if ($this->hasOrder()) {
            foreach ($this->order_columns as $col) {
                $this->selectQuery->order(
                    $col[0]
                    . ' '
                    . ( $col[1] === self::SORT_DESC ? 'DESC' : 'ASC')
                );
            }
        }

        $this->countQuery = clone($this->baseQuery);
        if ($this->countColumns === null) {
            $this->countColumns = array('cnt' => 'COUNT(*)');
        }
        $this->countQuery->columns($this->countColumns);
    }
    
    protected function beforeCreatingCountQuery()
    {
    }

    protected function beforeCreatingSelectQuery()
    {
    }

    public function count()
    {
        return $this->db->fetchOne($this->getCountQuery());
    }
    
    public function fetchAll()
    {
        return $this->db->fetchAll($this->getSelectQuery());
    }

    public function fetchRow()
    {
        return $this->db->fetchRow($this->getSelectQuery());
    }

    public function fetchOne()
    {
        return $this->db->fetchOne($this->getSelectQuery());
    }

    public function fetchPairs()
    {
        return $this->db->fetchPairs($this->getSelectQuery());
    }

    public function dump()
    {
        return "QUERY\n=====\n"
             . $this->getSelectQuery()
             . "\n\nCOUNT\n=====\n"
             . $this->getCountQuery()
             . "\n\n";
    }

    public function __toString()
    {
        return (string) $this->getSelectQuery();
    }
}
