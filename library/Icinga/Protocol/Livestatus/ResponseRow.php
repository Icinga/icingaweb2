<?php

namespace Icinga\Protocol\Livestatus;

use SplFixedArray;

class ResponseRow
{
    protected $raw;

    protected $query;

    public function __construct(SplFixedArray $raw, Query $query)
    {
        $this->raw   = $raw;
        $this->query = $query;
    }
}
