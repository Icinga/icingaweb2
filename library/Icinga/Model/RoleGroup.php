<?php

/* Icinga Web 2 | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * A database model for Icinga Web role-group table
 *
 * @property int $role_id Role identifier
 * @property string $group_name Group name
 * @property Role $role Role object
 */
class RoleGroup extends Model
{
    public function getTableName(): string
    {
        return 'icingaweb_role_group';
    }

    public function getKeyName(): array
    {
        return ['group_name', 'role_id'];
    }

    public function getColumns(): array
    {
        return [];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('icingaweb_role', Role::class) // TODO(ak): make 'role' working
            ->setCandidateKey('role_id')
            ->setJoinType('INNER');
    }
}
