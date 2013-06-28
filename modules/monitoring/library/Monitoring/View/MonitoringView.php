<?php

namespace Icinga\Monitoring\View;

use Icinga\Data\AbstractQuery;
use Icinga\Data\Filter;

class MonitoringView extends AbstractQuery
{
    protected $query;

    protected $availableColumns = array();

    protected $specialFilters = array();

    protected $sortDefaults = array();

    public function hasColumn($column)
    {
        return in_array($column, $this->availableColumns);
    }
    
    public function getAvailableColumns()
    {
        return $this->availableColumns;
    }

    public function applyRequest($request)
    {
        return $this->applyRequestFilters($request)
                    ->applyRequestSorting($request);
    }

    protected function applyRequestSorting($request)
    {
        return $this->order(
            $request->getParam('sort', $this->availableColumns[0]),
            $request->getParam('dir')
        );
    }

    protected function applyRequestFilters($request)
    {
        foreach ($request->getParams() as $key => $value) {
            if ($this->isValidFilterColumn($key)) {
                $this->where($key, $value);
            }
        }
        return $this;
    }

    // TODO: applyAuthFilters(Auth $auth = null)

    public function applyFilters($filters)
    {
        foreach ($filters as $col => $filter) {
            $this->where($col, $filter);
        }
        return $this;
    }

    public function getAppliedFilter()
    {
        return new Filter($this->filters);
    }

    protected function getDefaultSortDir($col)
    {
        if (isset($this->sortDefaults[$col]['default_dir'])) {
            return $this->sortDefaults[$col]['default_dir'];
        }
        return self::SORT_ASC;
    }

    public function getQuery()
    {

        if ($this->query === null) {
			$class = substr(array_pop(preg_split('|\\\|', get_class($this))), 0, -4) . 'Query';
			$class = '\\' . get_class($this->ds) . '\\Query\\' . $class;

            $query = new $class($this->ds, $this->columns);
            foreach ($this->filters as $f) {
                $query->where($f[0], $f[1]);
            }
            foreach ($this->order_columns as $col) {
                if (isset($this->sortDefaults[$col[0]]['columns'])) {
                    foreach ($this->sortDefaults[$col[0]]['columns'] as $c) {
                        $query->order($c, $col[1]);
                    }
                } else {
                    $query->order($col[0], $col[1]);
                }
            }
            $this->query = $query;
        }
        if ($this->hasLimit()) {
            $this->query->limit($this->getLimit(), $this->getOffset());
        }
        return $this->query;
    }

    public function count()
    {
        return $this->getQuery()->count();
    }

    public function fetchAll()
    {
        return $this->getQuery()->fetchAll();
    }

    public function fetchRow()
    {
        return $this->getQuery()->fetchRow();
    }

    public function fetchColumn()
    {
        return $this->getQuery()->fetchColumn();
    }

    public function fetchPairs()
    {
        return $this->getQuery()->fetchPairs();
    }

    public function isValidFilterColumn($column)
    {
        if (in_array($column, $this->specialFilters)) {
            return true;
        }
        return in_array($column, $this->availableColumns);
    }
}
