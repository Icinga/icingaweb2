<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\DataArray;

use ArrayIterator;
use Icinga\Data\Selectable;
use Icinga\Data\SimpleQuery;

class ArrayDatasource implements Selectable
{
    /**
     * The array being used as data source
     *
     * @var array
     */
    protected $data;

    /**
     * The current result
     *
     * @var array
     */
    protected $result;

    /**
     * The result of a counted query
     *
     * @var int
     */
    protected $count;

    /**
     * The name of the column to map array keys on
     *
     * In case the array being used as data source provides keys of type string,this name
     * will be used to set such as column on each row, if the column is not set already.
     *
     * @var string
     */
    protected $keyColumn;

    /**
     * Create a new data source for the given array
     *
     * @param   array   $data   The array you're going to use as a data source
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Set the name of the column to map array keys on
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setKeyColumn($name)
    {
        $this->keyColumn = $name;
        return $this;
    }

    /**
     * Return the name of the column to map array keys on
     *
     * @return  string
     */
    public function getKeyColumn()
    {
        return $this->keyColumn;
    }

    /**
     * Provide a query for this data source
     *
     * @return  SimpleQuery
     */
    public function select()
    {
        return new SimpleQuery(clone $this);
    }

    /**
     * Fetch and return all rows of the given query's result set using an iterator
     *
     * @param   SimpleQuery     $query
     *
     * @return  ArrayIterator
     */
    public function query(SimpleQuery $query)
    {
        return new ArrayIterator($this->fetchAll($query));
    }

    /**
     * Fetch and return a column of all rows of the result set as an array
     *
     * @param   SimpleQuery     $query
     *
     * @return  array
     */
    public function fetchColumn(SimpleQuery $query)
    {
        $result = array();
        foreach ($this->getResult($query) as $row) {
            $arr = (array) $row;
            $result[] = array_shift($arr);
        }

        return $result;
    }

    /**
     * Fetch and return all rows of the given query's result as a flattened key/value based array
     *
     * @param   SimpleQuery     $query
     *
     * @return  array
     */
    public function fetchPairs(SimpleQuery $query)
    {
        $result = array();
        $keys = null;
        foreach ($this->getResult($query) as $row) {
            if ($keys === null) {
                $keys = array_keys((array) $row);
                if (count($keys) < 2) {
                    $keys[1] = $keys[0];
                }
            }

            $result[$row->{$keys[0]}] = $row->{$keys[1]};
        }

        return $result;
    }

    /**
     * Fetch and return the first row of the given query's result
     *
     * @param   SimpleQuery     $query
     *
     * @return  object|false    The row or false in case the result is empty
     */
    public function fetchRow(SimpleQuery $query)
    {
        $result = $this->getResult($query);
        if (empty($result)) {
            return false;
        }

        return array_shift($result);
    }

    /**
     * Fetch and return all rows of the given query's result as an array
     *
     * @param   SimpleQuery     $query
     *
     * @return  array
     */
    public function fetchAll(SimpleQuery $query)
    {
        return $this->getResult($query);
    }

    /**
     * Count all rows of the given query's result
     *
     * @param   SimpleQuery     $query
     *
     * @return  int
     */
    public function count(SimpleQuery $query)
    {
        if ($this->count === null) {
            $this->count = count($this->createResult($query));
        }

        return $this->count;
    }

    /**
     * Create and return the result for the given query
     *
     * @param   SimpleQuery     $query
     *
     * @return  array
     */
    protected function createResult(SimpleQuery $query)
    {
        $columns = $query->getColumns();
        $filter = $query->getFilter();
        $offset = $query->hasOffset() ? $query->getOffset() : 0;
        $limit = $query->hasLimit() ? $query->getLimit() : 0;

        $foundStringKey = false;
        $result = array();
        $skipped = 0;
        foreach ($this->data as $key => $row) {
            if (is_string($key) && $this->keyColumn !== null && !isset($row->{$this->keyColumn})) {
                $row = clone $row; // Make sure that this won't affect the actual data
                $row->{$this->keyColumn} = $key;
            }

            if (! $filter->matches($row)) {
                continue;
            } elseif ($skipped < $offset) {
                $skipped++;
                continue;
            }

            // Get only desired columns if asked so
            if (! empty($columns)) {
                $filteredRow = (object) array();
                foreach ($columns as $alias => $name) {
                    if (! is_string($alias)) {
                        $alias = $name;
                    }

                    if (isset($row->$name)) {
                        $filteredRow->$alias = $row->$name;
                    } else {
                        $filteredRow->$alias = null;
                    }
                }
            } else {
                $filteredRow = $row;
            }

            $foundStringKey |= is_string($key);
            $result[$key] = $filteredRow;

            if (count($result) === $limit) {
                break;
            }
        }

        // Sort the result
        if ($query->hasOrder()) {
            if ($foundStringKey) {
                uasort($result, array($query, 'compare'));
            } else {
                usort($result, array($query, 'compare'));
            }
        } elseif (! $foundStringKey) {
            $result = array_values($result);
        }

        return $result;
    }

    /**
     * Return whether a query result exists
     *
     * @return  bool
     */
    protected function hasResult()
    {
        return $this->result !== null;
    }

    /**
     * Set the current result
     *
     * @param   array   $result
     *
     * @return  $this
     */
    protected function setResult(array $result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * Return the result for the given query
     *
     * @param   SimpleQuery     $query
     *
     * @return  array
     */
    protected function getResult(SimpleQuery $query)
    {
        if (! $this->hasResult()) {
            $this->setResult($this->createResult($query));
        }

        return $this->result;
    }
}
