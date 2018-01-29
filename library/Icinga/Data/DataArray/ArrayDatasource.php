<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\DataArray;

use ArrayIterator;
use Icinga\Data\Selectable;

class ArrayDatasource implements Selectable
{
    /**
     * The array being used as data source
     *
     * @var array
     */
    protected $data;

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
     * @return  ArrayQuery
     */
    public function select()
    {
        return new ArrayQuery($this);
    }

    /**
     * Fetch and return all rows of the given query's result set using an iterator
     *
     * @param   ArrayQuery     $query
     *
     * @return  ArrayIterator
     */
    public function query(ArrayQuery $query)
    {
        return new ArrayIterator($this->fetchAll($query));
    }

    /**
     * Fetch and return a column of all rows of the result set as an array
     *
     * @param   ArrayQuery     $query
     *
     * @return  array
     */
    public function fetchColumn(ArrayQuery $query)
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
     * @param   ArrayQuery     $query
     *
     * @return  array
     */
    public function fetchPairs(ArrayQuery $query)
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
     * @param   ArrayQuery     $query
     *
     * @return  object|false    The row or false in case the result is empty
     */
    public function fetchRow(ArrayQuery $query)
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
     * @param   ArrayQuery     $query
     *
     * @return  array
     */
    public function fetchAll(ArrayQuery $query)
    {
        return $this->getResult($query);
    }

    /**
     * Count all rows of the given query's result
     *
     * @param   ArrayQuery     $query
     *
     * @return  int
     */
    public function count(ArrayQuery $query)
    {
        return count($this->getResult($query));
    }

    /**
     * Create and return the result for the given query
     *
     * @param   ArrayQuery     $query
     *
     * @return  array
     */
    protected function createResult(ArrayQuery $query)
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
     * Return the result for the given query
     *
     * @param   ArrayQuery     $query
     *
     * @return  array
     */
    protected function getResult(ArrayQuery $query)
    {
        $result = $query->getResult();
        if ($result === null) {
            $result = $this->createResult($query);
            $query->setResult($result);
        }

        return $result;
    }
}
