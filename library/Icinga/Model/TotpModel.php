<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;

class TotpModel extends Model
{
    public function getTableName()
    {
        return 'icingaweb_totp';
    }

    public function getKeyName()
    {
        return 'username';
    }

    public function getColumns()
    {
        return [
            'username',
            'secret',
            'ctime'
        ];
    }
}
