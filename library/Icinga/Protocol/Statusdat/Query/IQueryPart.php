<?php

namespace Icinga\Protocol\Statusdat\Query;


interface IQueryPart
{
     public function __construct($expression = null,&$value = array());
     public function filter(array &$base, &$idx=null);
}
