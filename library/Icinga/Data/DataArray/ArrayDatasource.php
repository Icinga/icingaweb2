<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\DataArray;

use Icinga\Data\Selectable;
use Icinga\Data\SimpleQuery;

class ArrayDatasource implements Selectable
{
    protected $data;

    protected $result;

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
     * Constructor, create a new Datasource for the given Array
     *
     * @param array $array The array you're going to use as a data source
     */
    public function __construct(array $array)
    {
        $this->data = (array) $array;
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
     * Instantiate a Query object
     *
     * @return SimpleQuery
     */
    public function select()
    {
        return new SimpleQuery($this);
    }

    public function fetchColumn(SimpleQuery $query)
    {
        $result = array();
        foreach ($this->getResult($query) as $row) {
            $arr = (array) $row;
            $result[] = array_shift($arr);
        }
        return $result;
    }

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

    public function fetchRow(SimpleQuery $query)
    {
        $result = $this->getResult($query);
        if (empty($result)) {
            return false;
        }
        return $result[0];
    }

    public function fetchAll(SimpleQuery $query)
    {
        return $this->getResult($query);
    }

    public function count(SimpleQuery $query)
    {
        $this->createResult($query);
        return count($this->result);
    }

    protected function createResult(SimpleQuery $query)
    {
        if ($this->hasResult()) {
            return $this;
        }

        $columns = $query->getColumns();
        $filter = $query->getFilter();
        $foundStringKey = false;
        $result = array();
        foreach ($this->data as $key => $row) {
            if (is_string($key) && $this->keyColumn !== null && !isset($row->{$this->keyColumn})) {
                $row = clone $row; // Make sure that this won't affect the actual data
                $row->{$this->keyColumn} = $key;
            }

            if (! $filter->matches($row)) {
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

        $this->setResult($result);
        return $this;
    }

    protected function getLimitedResult($query)
    {
        if ($query->hasLimit()) {
            if ($query->hasOffset()) {
                $offset = $query->getOffset();
            } else {
                $offset = 0;
            }
            return array_slice($this->result, $offset, $query->getLimit());
        } else {
            return $this->result;
        }
    }

    protected function hasResult()
    {
        return $this->result !== null;
    }

    protected function setResult($result)
    {
        return $this->result = $result;
    }

    protected function getResult(SimpleQuery $query)
    {
        if (! $this->hasResult()) {
            $this->createResult($query);
        }
        return $this->getLimitedResult($query);
    }
}
