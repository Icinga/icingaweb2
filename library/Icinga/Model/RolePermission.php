<?php

/* Icinga Web 2 | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * A database model for Icinga Web role-permission table
 *
 * @property int $role_id Role identifier
 * @property string $permission Permission name
 * @property bool $allowed Whether the permission is allowed
 * @property bool $denied Whether the permission is denied
 * @property Role $role Role object
 */
class RolePermission extends Model
{
    public function getTableName(): string
    {
        return 'icingaweb_role_permission';
    }

    public function getKeyName(): array
    {
        return ['role_id', 'permission'];
    }

    public function getColumns(): array
    {
        return ['allowed', 'denied'];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new BoolCast(['allowed', 'denied']));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('icingaweb_role', Role::class) // TODO(ak): make 'role' working
            ->setCandidateKey('role_id')
            ->setJoinType('INNER');
    }
}
