<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;

class TotpModel extends Model
{
    public function getTableName(): string
    {
        return 'icingaweb_totp';
    }

    public function getKeyName(): string
    {
        return 'username';
    }

    public function getColumns(): array
    {
        return [
            'username',
            'secret',
            'ctime'
        ];
    }
}
