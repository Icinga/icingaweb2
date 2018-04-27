<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

/**
 * Stores data in memory or a temporary file not to get out of memory
 */
class Buffer extends StreamWrapper
{
    /**
     * Buffer constructor
     */
    public function __construct()
    {
        parent::__construct($this->assertSuccessfulFunctionCall('fopen', array('php://temp', 'w+b')));
    }
}
