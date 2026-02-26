<?php

/* Icinga Web 2 | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use DateTime;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * A database model for Icinga Web role table
 *
 * @property int $id Unique identifier
 * @property ?int $parent_id Inherited role identifier (optional)
 * @property string $name Unique name
 * @property bool $unrestricted Whether restrictions don't apply
 * @property DateTime $ctime The insert time
 * @property ?DateTime $mtime The modification time (optional)
 * @property ?Role $parent Inherited role (optional)
 * @property Role[] $children Inheriting roles
 * @property RoleUser[] $users Users this role applies to
 * @property RoleGroup[] $groups Groups this role applies to
 * @property RolePermission[] $permissions Permissions this role allows/denies
 * @property RoleRestriction[] $restrictions Restrictions this role imposes
 */
class Role extends Model
{
    public function getTableName(): string
    {
        return 'icingaweb_role';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return ['parent_id', 'name', 'unrestricted', 'ctime', 'mtime'];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new BoolCast(['unrestricted']));
        $behaviors->add(new MillisecondTimestamp(['ctime', 'mtime']));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('parent', self::class)
            ->setCandidateKey('parent_id')
            ->setJoinType('LEFT');

        $relations->hasMany('children', self::class)
            ->setForeignKey('parent_id')
            ->setJoinType('LEFT');

        $relations->hasMany('users', RoleUser::class)
            ->setForeignKey('role_id')
            ->setJoinType('LEFT');

        $relations->hasMany('groups', RoleGroup::class)
            ->setForeignKey('role_id')
            ->setJoinType('LEFT');

        $relations->hasMany('permissions', RolePermission::class)
            ->setForeignKey('role_id')
            ->setJoinType('LEFT');

        $relations->hasMany('restrictions', RoleRestriction::class)
            ->setForeignKey('role_id')
            ->setJoinType('LEFT');
    }
}
