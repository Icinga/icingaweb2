<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\DataArray;

use Icinga\Data\Selectable;
use Icinga\Data\SimpleQuery;

class ArrayDatasource implements Selectable
{
    protected $data;
    
    protected $result;

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
        $result = array();

        $columns = $query->getColumns();
        $filter = $query->getFilter();
        foreach ($this->data as & $row) {

            if (! $filter->matches($row)) {
                continue;
            }

            // Get only desired columns if asked so
            if (empty($columns)) {
                $result[] = $row;
            } else {
                $c_row = (object) array();
                foreach ($columns as $alias => $key) {
                    if (is_int($alias)) {
                        $alias = $key;
                    }
                    if (isset($row->$key)) {
                        $c_row->$alias = $row->$key;
                    } else {
                        $c_row->$alias = null;
                    }
                }
                $result[] = $c_row;
            }
        }

        // Sort the result

        if ($query->hasOrder()) {
            usort($result, array($query, 'compare'));
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
