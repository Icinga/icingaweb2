<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Backend;

use Icinga\Web\Paginator\Adapter\QueryAdapter;

/**
 * Class Query
 * @package Icinga\Backend
 */
abstract class Query
{

    /**
     * @var AbstractBackend
     */
    protected $backend;

    /**
     * @var array
     */
    protected $columns = array();

    /**
     * @var array
     */
    protected $available_columns = array();

    /**
     * @param null $count
     * @param null $offset
     * @return mixed
     */
    abstract public function limit($count = null, $offset = null);

    /**
     * @param $column
     * @param null $value
     * @return mixed
     */
    abstract public function where($column, $value = null);

    /**
     * @param string $column
     * @param null $dir
     * @return mixed
     */
    abstract public function order($column = '', $dir = null);

    /**
     * @return mixed
     */
    abstract public function fetchAll();

    /**
     * @return mixed
     */
    abstract public function fetchRow();

    /**
     * @return mixed
     */
    abstract public function fetchPairs();

    /**
     * @return mixed
     */
    abstract public function fetchOne();

    /**
     * @return mixed
     */
    abstract public function count();

    /**
     * @param AbstractBackend $backend
     * @param array $columns
     * @return \Icinga\Backend\Query
     */
    public function __construct(AbstractBackend $backend, $columns = array())
    {
        $this->backend = $backend;
        if (empty($columns) || $columns === '*') {
            $this->columns = $this->available_columns;
        } else {
            $this->columns = $columns;
        }
        $this->init();
    }

    /**
     * @param array $filters
     * @return $this
     */
    public function applyFilters($filters = array())
    {
        foreach ($filters as $key => $val) {
            $this->where($key, $val);
        }
        return $this;
    }

    /**
     * @return mixed
     */
    abstract protected function init();

    /*
     *
     */
    protected function finalize()
    {
    }

    /**
     * Return a pagination adapter for the current query
     *
     * @param null $limit
     * @param null $page
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
            new QueryAdapter($this)
        );
        $paginator->setItemCountPerPage($limit);
        $paginator->setCurrentPageNumber($page);
        return $paginator;
    }
}
