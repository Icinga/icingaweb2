<?php

class Test
{
    protected $filters = array();

    public function work()
    {
        foreach ($this->getFilters() as $key => &$value) {
            $value = 1;
        }
    }

    public function &getFilters()
    {
        return $this->filters;
    }
}

$x = new Test();
$b =& $x->getFilters();
$b[1] = 0;
var_dump($b, $x->getFilters());die;
