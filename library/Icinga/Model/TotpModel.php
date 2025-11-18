<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;

class TotpModel extends Model
{

    /**
     * @inheritDoc
     */
    public function getTableName()
    {
        return 'icingaweb_totp';
    }

    /**
     * @inheritDoc
     */
    public function getKeyName()
    {
        return 'username';
    }

    /**
     * @inheritDoc
     */
    public function getColumns()
    {
        return [
            'username',
            'secret',
            'ctime'
        ];
    }
}
