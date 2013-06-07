<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol;

/**
 * Class AbstractQuery
 * @package Icinga\Protocol
 */
abstract class AbstractQuery
{
    /**
     *
     */
    const SORT_ASC = 1;

    /**
     *
     */
    const SORT_DESC = -1;

    /**
     * @param $key
     * @param null $val
     * @return mixed
     */
    abstract public function where($key, $val = null);

    /**
     * @param $col
     * @return mixed
     */
    abstract public function order($col);

    /**
     * @param null $count
     * @param null $offset
     * @return mixed
     */
    abstract public function limit($count = null, $offset = null);

    /**
     * @param $table
     * @param null $columns
     * @return mixed
     */
    abstract public function from($table, $columns = null);

    /**
     * @return bool
     */
    public function hasOrder()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasColumns()
    {
        return false;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return array();
    }

    /**
     * @return bool
     */
    public function hasLimit()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasOffset()
    {
        return false;
    }

    /**
     * @return null
     */
    public function getLimit()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getOffset()
    {
        return null;
    }
}
