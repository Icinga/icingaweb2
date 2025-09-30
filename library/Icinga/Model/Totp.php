<?php

namespace Icinga\Model;

use ipl\Orm\Model;

class Totp extends Model
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
