<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Filter;

use Icinga\Exception\IcingaException;

class FilterParseException extends IcingaException
{
    protected $char;

    protected $charPos;

    public function __construct($message, $filter, $char, $charPos, ...$additional)
    {
        parent::__construct($message, $filter, $char, $charPos, ...$additional);

        $this->char = $char;
        $this->charPos = $charPos;
    }

    public function getChar()
    {
        return $this->char;
    }

    public function getCharPos()
    {
        return $this->charPos;
    }
}
