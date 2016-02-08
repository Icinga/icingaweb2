<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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
