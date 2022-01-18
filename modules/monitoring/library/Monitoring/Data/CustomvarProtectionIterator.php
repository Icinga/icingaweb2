<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Monitoring\Data;

use Icinga\Module\Monitoring\Object\MonitoredObject;
use IteratorIterator;

class CustomvarProtectionIterator extends IteratorIterator
{
    const IS_CV_RE = '~^_(host|service)_([a-zA-Z0-9_]+)$~';

    public function current(): object
    {
        $row = parent::current();

        foreach ($row as $col => $val) {
            if (preg_match(self::IS_CV_RE, $col, $m)) {
                $row->$col = MonitoredObject::protectCustomVars([$m[2] => $val])[$m[2]];
            }
        }

        return $row;
    }
}
