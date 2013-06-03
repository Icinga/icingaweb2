<?php

namespace Icinga\Backend;
abstract class Query
{

    protected $backend;
    protected $columns = array();
    protected $available_columns = array();

    abstract public function limit($count = null, $offset = null);
    abstract public function where($column, $value = null);
    abstract public function order($column = '', $dir = null);
    abstract public function fetchAll();
    abstract public function fetchRow();
    abstract public function fetchPairs();
    abstract public function fetchOne();
    abstract public function count();

    public function __construct(\Icinga\Backend\AbstractBackend $backend, $columns = array())
    {
        $this->backend = $backend;
        if (empty($columns) || $columns === '*') {
            $this->columns = $this->available_columns;
        } else {
            $this->columns = $columns;
        }
        $this->init();
    }

    public function applyFilters($filters = array())
    {
        foreach ($filters as $key => $val) {
            $this->where($key, $val);
        }
        return $this;
    }

    abstract protected function init();

    protected function finalize() {}

    /**
     * Return a pagination adapter for the current query
     *
     * @return \Zend_Paginator
     */
    public function paginate($limit = null, $page = null)
    {
        $this->finalize();
        $request = \Zend_Controller_Front::getInstance()->getRequest();
        if ($page === null) {
            $page = $request->getParam('page', 0);
        }
        if ($limit === null) {
            $limit = $request->getParam('limit', 20);
        }
        $paginator = new \Zend_Paginator(
            new \Icinga\Web\Paginator\Adapter\QueryAdapter($this)
        );
        $paginator->setItemCountPerPage($limit);
        $paginator->setCurrentPageNumber($page);
        return $paginator;
    }
}

