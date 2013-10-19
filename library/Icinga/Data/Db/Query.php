<?php

namespace Icinga\Data\Db;

use Icinga\Data\Optional;
use Icinga\Data\The;
use Icinga\Filter\Query\Node;
use Icinga\Filter\Query\Tree;
use Zend_Db_Select;
use Icinga\Data\BaseQuery;

/**
 * Db/Query class for implementing database queries
 */
class Query extends BaseQuery
{
    /**
     * Zend_Db_Adapter_Abstract
     *
     *
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
    private $selectQuery;

    /**
     * Select object used for count query
     */
    private $countQuery;

    /**
     * Allow to override COUNT(*)
     */
    protected $countColumns;

    protected $useSubqueryCount = false;

    protected $countCache;

    protected $maxCount;

    protected function init()
    {
        $this->db = $this->ds->getConnection();
        $this->baseQuery = $this->db->select();
    }

    /**
     * Return the raw base query
     *
     * Modifications on this requires a call to Query::refreshQueryObjects()
     *
     * @return Zend_Db_Select
     *
     */
    public function getRawBaseQuery()
    {
        return $this->baseQuery;
    }

    /**
     * Recreate the select and count queries
     *
     * Required when external modifications are made in the baseQuery
     */
    public function refreshQueryObjects()
    {
        $this->createQueryObjects();
    }


    /**
     * Return the select query and initialize it if not done yet
     *
     * @return Zend_Db_Select
     */
    public function getSelectQuery()
    {
        if ($this->selectQuery === null) {
            $this->createQueryObjects();
        }

        if ($this->hasLimit()) {
            $this->selectQuery->limit($this->getLimit(), $this->getOffset());
        }
        return $this->selectQuery;
    }

    /**
     * Return the current count query and initialize it if not done yet
     *
     * @return Zend_Db_Select
     */
    public function getCountQuery()
    {
        if ($this->countQuery === null) {
            $this->createQueryObjects();
        }
        return $this->countQuery;
    }

    /**
     * Create the Zend_Db select query for this query
     */
    private function createSelectQuery()
    {
        $this->selectQuery = clone($this->baseQuery);
        $this->selectQuery->columns($this->getColumns());
        if ($this->hasOrder()) {
            foreach ($this->getOrderColumns() as $col) {
                $this->selectQuery->order(
                    $col[0] . ' ' . (($col[1] === self::SORT_DESC) ? 'DESC' : 'ASC')
                );
            }
        }
    }

    /**
     * Create a countquery by using the select query as a subselect and count it's result
     *
     * This is a rather naive approach and not suitable for complex queries or queries with many results
     *
     * @return Zend_Db_Select       The query object representing the count
     */
    private function createCountAsSubQuery()
    {
        $query = clone($this->selectQuery);
        if ($this->maxCount === null) {
            return $this->db->select()->from($query, 'COUNT(*)');
        } else {
            return $this->db->select()->from(
                $query->reset('order')->limit($this->maxCount),
                'COUNT(*)'
            );
        }
    }

    /**
     * Create a custom count query based on the columns set in countColumns
     *
     * @return Zend_Db_Select       The query object representing the count
     */
    private function createCustomCountQuery()
    {
        $query = clone($this->baseQuery);
        if ($this->countColumns === null) {
            $this->countColumns = array('cnt' => 'COUNT(*)');
        }
        $query->columns($this->countColumns);
        return $query;
    }

    /**
     * Create a query using the selected operation
     *
     * @see Query::createCountAsSubQuery()      Used when useSubqueryCount is true
     * @see Query::createCustomCountQuery()     Called when useSubqueryCount is false
     */
    private function createCountQuery()
    {
        if ($this->useSubqueryCount) {
            $this->countQuery = $this->createCountAsSubquery();
        } else {
            $this->countQuery = $this->createCustomCountQuery();
        }
    }


    protected function beforeQueryCreation()
    {

    }

    protected function afterQueryCreation()
    {

    }

    /**
     * Create the Zend_Db select and count query objects for this instance
     */
    private function createQueryObjects()
    {
        $this->beforeQueryCreation();
        $this->applyFilter();
        $this->createSelectQuery();
        $this->createCountQuery();
        $this->afterQueryCreation();
    }

    /**
     * Query the database and fetch the result count of this query
     *
     * @return int      The result count of this query as returned by the database
     */
    public function count()
    {
        if ($this->countCache === null) {
            $this->countCache = $this->db->fetchOne($this->getCountQuery());
        }
        return $this->countCache;
    }

    /**
     * Query the database and return all results
     *
     * @return array        An array containing subarrays with all results contained in the database
     */
    public function fetchAll()
    {
        return $this->db->fetchAll($this->getSelectQuery());
    }

    /**
     * Query the database and return the next result row
     *
     * @return array        An array containing the next row of the database result
     */
    public function fetchRow()
    {
        return $this->db->fetchRow($this->getSelectQuery());
    }

    /**
     * Query the database and return a single column of the result
     *
     * @return array        An array containing the first column of the result
     */
    public function fetchColumn()
    {
        return $this->db->fetchCol($this->getSelectQuery());
    }

    /**
     * Query the database and return a single result
     *
     * @return array       An associative array containing the first result
     */
    public function fetchOne()
    {
        return $this->db->fetchOne($this->getSelectQuery());
    }

    /**
     * Query the database and return key=>value pairs using hte first two columns
     *
     * @return array        An array containing key=>value pairs
     */
    public function fetchPairs()
    {
        return $this->db->fetchPairs($this->getSelectQuery());
    }

    /**
     * Return the select and count query as a textual representation
     *
     * @return string       An String containing the select and count query, using unix style newlines
     *                      as linebreaks
     */
    public function dump()
    {
        return "QUERY\n=====\n"
        . $this->getSelectQuery()
        . "\n\nCOUNT\n=====\n"
        . $this->getCountQuery()
        . "\n\n";
    }

    /**
     * Return the select query
     *
     * The paginator expects this, so we can't use debug output here
     *
     * @return Zend_Db_Select
     */
    public function __toString()
    {
        return strval($this->getSelectQuery());
    }

    public function applyFilter()
    {
        $parser = new TreeToSqlParser($this);
        $parser->treeToSql($this->getFilter(), $this->baseQuery);
        $this->clearFilter();
    }
}
