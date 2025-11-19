<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use DateTime;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

/**
 * Database model for the table that stores the secrets for 2FA
 *
 * @property string   $username The user who owns the secret
 * @property string   $secret   The secret from which the tokens are generated
 * @property DateTime $ctime    The creation time of the secret
 */
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

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new MillisecondTimestamp(['ctime']));
    }
}
