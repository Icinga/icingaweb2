<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;

//TODO: Is copied from icingadb-web
class BoolCast extends PropertyBehavior
{
    public function fromDb($value, $key, $_)
    {
        switch ($value) {
            case 'y':
                return true;
            case 'n':
                return false;
            default:
                return $value;
        }
    }

    public function toDb($value, $key, $_)
    {
        if (is_string($value)) {
            return $value;
        }

        return $value ? 'y' : 'n';
    }
}
