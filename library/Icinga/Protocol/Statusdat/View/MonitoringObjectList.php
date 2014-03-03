<?php

namespace Icinga\Protocol\Statusdat\View;

use \Iterator;
use \Countable;
use \ArrayAccess;
use \Exception;

/**
 * Wrapper around an array of monitoring objects that can be enhanced with an optional
 * object that extends AccessorStrategy. This will act as a dataview and provide
 * normalized access to the underlying data (mapping properties, retrieving additional data)
 *
 * If not Accessor is set, this class just behaves like a normal Iterator and returns
 * the underlying objects.
 *
 * If the dataset contains arrays instead of objects, they will be cast to objects.
 *
 */
class MonitoringObjectList implements Iterator, Countable, ArrayAccess
{
    private $dataSet = array();
    private $position = 0;
    private $dataView = null;

    public function __construct(array &$dataset, AccessorStrategy $dataView = null)
    {
        $this->dataSet = $dataset;
        $this->position = 0;
        $this->dataView = $dataView;
    }

    public function count()
    {
        return count($this->dataSet);
    }

    public function setPosition($pos)
    {
        $this->position = $pos;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        if ($this->dataView) {
            return $this;
        }

        return $this->dataSet[$this->position];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->position < count($this->dataSet);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->position = 0;
    }

    public function __isset($name)
    {
        return $this->dataView->exists($this->dataSet[$this->position], $name);
    }

    public function __get($name)
    {
        return $this->dataView->get($this->dataSet[$this->position], $name);
    }

    public function __set($name, $value)
    {
        throw new Exception("Setting is currently not available for objects");
    }

    public function offsetExists($offset)
    {
        return count($this->dataSet) < $offset;
    }

    public function offsetGet($offset)
    {
        $res = new MonitoringObjectList($this->dataSet, $this->dataView);
        $res->position = $offset;
        return $res;
    }

    public function offsetSet($offset, $value)
    {
        // non mutable
    }

    public function offsetUnset($offset)
    {
        // non mutable
    }
}
