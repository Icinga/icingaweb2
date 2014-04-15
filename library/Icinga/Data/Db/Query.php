<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Db;

use Zend_Db_Select;
use Icinga\Data\BaseQuery;
use Icinga\Application\Benchmark;

/**
 * Database query class
 */
class Query extends BaseQuery
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Columns to select
     *
     * @var array
     */
    protected $columns;

    /**
     * Select query
     *
     * @var Zend_Db_Select
     */
    protected $select;

    /**
     * Whether to use a subquery for counting
     *
     * When the query is distinct or has a HAVING or GROUP BY clause this must be set to true
     *
     * @var bool
     */
    protected $useSubqueryCount = false;

    /**
     * Set the count maximum
     *
     * If the count maximum is set, count queries will not count more than that many rows. You should set this
     * property only for really heavy queries.
     *
     * @var int
     */
    protected $maxCount;

    /**
     * Count query result
     *
     * Count queries are only executed once
     *
     * @var int
     */
    protected $count;

    protected function init()
    {
        $this->db = $this->ds->getDbAdapter();
        $this->select = $this->db->select();
    }

    /**
     * Set the table and columns to select
     *
     * @param   string  $table
     * @param   array   $columns
     *
     * @return  self
     */
    public function from($table, array $columns = null)
    {
        $this->select->from($table, array());
        // Don't apply the columns to the select query yet because the count query uses a clone of the select query
        // but not its columns
        $this->columns($columns);
        return $this;
    }

    /**
     * Add a where condition to the query by and
     *
     * @param   string  $condition
     * @param   mixed   $value
     *
     * @return  self
     */
    public function where($condition, $value = null)
    {
        $this->select->where($condition, $value);
        return $this;
    }

    /**
     * Add a where condition to the query by or
     *
     * @param   string  $condition
     * @param   mixed   $value
     *
     * @return  self
     */
    public function orWhere($condition, $value = null)
    {
        $this->select->orWhere($condition, $value);
        return $this;
    }

    /**
     * Get the select query
     *
     * Applies order and limit if any
     *
     * @return Zend_Db_Select
     */
    public function getSelectQuery()
    {
        $select = clone $this->select;
        $select->columns($this->columns);
        if ($this->hasLimit() || $this->hasOffset()) {
            $select->limit($this->getLimit(), $this->getOffset());
        }
        if ($this->hasOrder()) {
            foreach ($this->getOrder() as $fieldAndDirection) {
                $select->order(
                    $fieldAndDirection[0] . ' ' . $fieldAndDirection[1]
                );
            }
        }
        return $select;
    }

    /**
     * Get the count query
     *
     * @return Zend_Db_Select
     */
    public function getCountQuery()
    {
        $count = clone $this->select;
        $columns = array('cnt' => 'COUNT(*)');
        if ($this->useSubqueryCount) {
            return $this->db->select()->from($count, $columns);
        }
        if ($this->maxCount !== null) {
            return $this->db->select()->from($count->limit($this->maxCount));
        }
        $count->columns(array('cnt' => 'COUNT(*)'));
        return $count;
    }

    /**
     * Count all rows of the result set
     *
     * @return int
     */
    public function count()
    {
        if ($this->count === null) {
            Benchmark::measure('DB is counting');
            $this->count = $this->db->fetchOne($this->getCountQuery());
            Benchmark::measure('DB finished count');
        }
        return $this->count;
    }

    /**
     * Return the select and count query as a textual representation
     *
     * @return string A string containing the select and count query, using unix style newlines as linebreaks
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
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getSelectQuery();
    }

    /**
     * Set the columns to select
     *
     * @param   array $columns
     *
     * @return  self
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }
}
