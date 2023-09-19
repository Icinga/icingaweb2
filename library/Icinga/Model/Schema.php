<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use DateTime;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

/**
 * A database model for Icinga Web schema version table
 *
 * @property int $id Unique identifier of the database schema entries
 * @property string $version The current schema version of Icinga Web
 * @property DateTime $timestamp The insert/modify time of the schema entry
 * @property bool $success Whether the database migration of the current version was successful
 * @property ?string $reason The reason why the database migration has failed
 */
class Schema extends Model
{
    public function getTableName(): string
    {
        return 'icingaweb_schema';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return [
            'version',
            'timestamp',
            'success',
            'reason'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new BoolCast(['success']));
        $behaviors->add(new MillisecondTimestamp(['timestamp']));
    }
}
