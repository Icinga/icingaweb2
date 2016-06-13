<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Eventdb;

use ArrayObject;

class Event extends ArrayObject
{
    public static $facilities = array(
        0 => 'kernel messages',
        1 => 'user-level messages',
        2 => 'mail system',
        3 => 'system daemons',
        4 => 'security/authorization messages',
        5 => 'messages generated internally by syslogd',
        6 => 'line printer subsystem',
        7 => 'network news subsystem',
        8 => 'UUCP subsystem',
        9 => 'clock daemon',
        10 => 'security/authorization messages',
        11 => 'FTP daemon',
        12 => 'NTP subsystem',
        13 => 'log audit',
        14 => 'log alert',
        15 => 'clock daemon',
        16 => 'local use 0',
        17 => 'local use 1',
        18 => 'local use 2',
        19 => 'local use 3',
        20 => 'local use 4',
        21 => 'local use 5',
        22 => 'local use 6',
        23 => 'local use 7'
    );

    public static $priorities = array(
        0 => 'EMERGENCY',
        1 => 'ALERT',
        2 => 'CRITICAL',
        3 => 'ERROR',
        4 => 'WARNING',
        5 => 'NOTICE',
        6 => 'INFORMATION',
        7 => 'DEBUG'
    );

    public static $types = array(
        0 => 'syslog',
        1 => 'smnp',
        2 => 'mail'
    );

    public function __construct($data)
    {
        parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
    }
    
    public function offsetGet($index)
    {
        if (! $this->offsetExists($index)) {
            return null;
        }
        $getter = 'get' . ucfirst($index);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return parent::offsetGet($index);
    }

    public function getFacility()
    {
        return static::$facilities[(int) parent::offsetGet('facility')];
    }

    public function getPriority()
    {
        return static::$priorities[(int) parent::offsetGet('priority')];
    }

    public function getType()
    {
        return static::$types[(int) parent::offsetGet('type')];
    }

    public static function fromData($data)
    {
        return new static($data);
    }
}
