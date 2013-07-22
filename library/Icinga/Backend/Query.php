<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @author Icinga Development Team <info@icinga.org>
 */
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
