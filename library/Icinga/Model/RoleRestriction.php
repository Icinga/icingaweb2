<?php

/* Icinga Web 2 | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * A database model for Icinga Web role-restriction table
 *
 * @property int $role_id Role identifier
 * @property string $restriction Restriction name
 * @property string $filter Filter of things the role is restricted to
 * @property Role $role Role object
 */
class RoleRestriction extends Model
{
    public function getTableName(): string
    {
        return 'icingaweb_role_restriction';
    }

    public function getKeyName(): array
    {
        return ['role_id', 'restriction'];
    }

    public function getColumns(): array
    {
        return ['filter'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('icingaweb_role', Role::class) // TODO(ak): make 'role' working
            ->setCandidateKey('role_id')
            ->setJoinType('INNER');
    }
}
