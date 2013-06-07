<?php

namespace Icinga\Data;

class ArrayDatasource implements DatasourceInterface
{
    protected $data;

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
     * Instantiate an ArrayQuery object
     *
     * @return ArrayQuery
     */
    public function select()
    {
        return new ArrayQuery($this);
    }

    public function fetchColumn(ArrayQuery $query)
    {
        $result = array();
        foreach ($this->getResult($query) as $row) {
            $arr = (array) $row;
            $result[] = array_shift($arr);
        }
        return $result;
    }

    public function fetchAll(ArrayQuery $query)
    {
        $result = $this->getResult($query);
        return $result;
    }

    public function count(ArrayQuery $query)
    {
        $this->createResult($query);
        return $query->getCount();
    }

    protected function createResult(ArrayQuery $query)
    {
        if ($query->hasResult()) {
            return $this;
        }
        $result = array();
        $filters = $query->listFilters();
        $columns = $query->listColumns();
        foreach ($this->data as & $row) {

            // Skip rows that do not match applied filters
            foreach ($filters as $f) {
                if ($row->{$f[0]} !== $f[1]) {
                    continue 2;
                }
            }

            // Get only desired columns if asked so
            if (empty($columns)) {
                $result[] = $row;
            } else {
                $c_row = (object) array();
                foreach ($columns as $key) {
                    if (isset($row->$key)) {
                        $c_row->$key = $row->$key;
                    } else {
                        $c_row->$key = null;
                    }
                }
                $result[] = $c_row;
            }
        }

        // Sort the result
        if ($query->hasOrder()) {
            usort($result, array($query, 'compare'));
        }

        $query->setResult($result);
        return $this;
    }

    protected function getResult(ArrayQuery $query)
    {
        if (! $query->hasResult()) {
            $this->createResult($query);
        }
        return $query->getLimitedResult();
    }
}
