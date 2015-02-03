<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
