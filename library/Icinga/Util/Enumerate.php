<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Util;

use Iterator;

/**
 * Class Enumerate
 *
 * @see https://docs.python.org/2/library/functions.html#enumerate
 *
 * @package Icinga\Util
 */
class Enumerate implements Iterator
{
    /**
     * @var Iterator
     */
    protected $iterator;

    /**
     * @var int
     */
    protected $key;

    /**
     * @param Iterator $iterator
     */
    public function __construct(Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    public function rewind()
    {
        $this->iterator->rewind();
        $this->key = 0;
    }

    public function next()
    {
        $this->iterator->next();
        ++$this->key;
    }

    public function valid()
    {
        return $this->iterator->valid();
    }

    public function current()
    {
        return $this->iterator->current();
    }

    public function key()
    {
        return $this->key;
    }
}
