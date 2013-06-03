<?php

namespace Icinga\Protocol;
abstract class AbstractQuery
{
    const SORT_ASC  = 1;
    const SORT_DESC = -1;

    abstract public function where($key, $val = null);
    abstract public function order($col);
    abstract public function limit($count = null, $offset = null);
    abstract public function from($table, $columns = null);
    
    public function hasOrder() 
    {
        return false;
    }
    
    public function hasColumns() 
    {
        return false;
    }
    
    public function getColumns() 
    {
        return array();
    }
    
    public function hasLimit() 
    {
        return false;
    }
    
    public function hasOffset() 
    {
        return false;
    }
    
    public function getLimit()
    {
        return null;
    }
    
    public function getOffset()
    {
        return null;
    }
    
}

