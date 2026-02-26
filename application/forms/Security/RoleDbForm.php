<?php

/* Icinga Web 2 | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Security;

use DateTime;
use Icinga\Application\Modules\Manager;
use Icinga\Model\Role;
use Icinga\Util\StringHelper;
use ipl\Sql\Connection;
use ipl\Sql\Delete;
use ipl\Sql\Insert;
use ipl\Sql\Select;
use ipl\Sql\Update;
use ipl\Stdlib\Filter;

/**
 * Form for managing roles stored in the database
 */
class RoleDbForm extends RoleForm
{
    /**
     * Database where the roles are stored
     *
     * @var ?Connection
     */
    private $db = null;

    public function fetchEntry()
    {
        $role = Role::on($this->db)->with('parent')->filter(Filter::equal('name', $this->getIdentifier()))->first();

        if (! $role) {
            return false;
        }

        $values = [
            'name'         => $role->name,
            'unrestricted' => (int) $role->unrestricted
        ];

        $users = [];
        $groups = [];
        $permissions = [];
        $refusals = [];
        $restrictions = [];

        foreach ($role->users as $user) {
            $users[] = $user->user_name;
        }

        foreach ($role->groups as $group) {
            $groups[] = $group->group_name;
        }

        foreach ($role->permissions as $permission) {
            if ($permission->allowed) {
                $permissions[$permission->permission] = true;

                if ($permission->permission === '*') {
                    $values[self::WILDCARD_NAME] = 1;
                }
            }

            if ($permission->denied) {
                $refusals[$permission->permission] = true;
            }
        }

        foreach ($role->restrictions as $restriction) {
            $restrictions[$restriction->restriction] = $restriction->filter;
        }

        if ($role->parent) {
            $values['parent'] = $role->parent->name;
        }

        if ($users) {
            sort($users);
            $values['users'] = implode(',', $users);
        }

        if ($groups) {
            sort($groups);
            $values['groups'] = implode(',', $groups);
        }

        if ($permissions || $refusals) {
            foreach ($this->providedPermissions as $moduleName => $permissionList) {
                $hasFullPerm = false;

                foreach ($permissionList as $name => $spec) {
                    if (array_key_exists($name, $permissions)) {
                        $values[$this->filterName($name)] = 1;

                        if (isset($spec['isFullPerm'])) {
                            $hasFullPerm = true;
                        }
                    }

                    if (array_key_exists($name, $refusals)) {
                        $values[$this->filterName(self::DENY_PREFIX . $name)] = 1;
                    }
                }

                if ($hasFullPerm) {
                    unset($values[$this->filterName(Manager::MODULE_PERMISSION_NS . $moduleName)]);
                }
            }
        }

        if ($restrictions) {
            foreach ($this->providedRestrictions as $restrictionList) {
                foreach ($restrictionList as $name => $spec) {
                    if (array_key_exists($name, $restrictions)) {
                        $values[$this->filterName($name)] = $restrictions[$name];
                    }
                }
            }
        }

        return (object) $values;
    }

    protected function entryExists(): bool
    {
        return Role::on($this->db)->filter(Filter::equal('name', $this->getIdentifier()))->count() > 0;
    }

    protected function insertEntry(): void
    {
        $values = $this->getValues();

        $this->db->transaction(function (Connection $db) use ($values) {
            $db->prepexec(
                (new Insert())
                    ->into('icingaweb_role')
                    ->columns(['parent_id', 'name', 'unrestricted', 'ctime'])
                    ->values([
                        $this->queryRoleId($db, $values['parent']),
                        $values['name'],
                        $values['unrestricted'] ? 'y' : 'n',
                        (new DateTime())->getTimestamp() * 1000
                    ])
            );

            $this->insertChildTables($db, $db->lastInsertId(), $values);
        });
    }

    protected function updateEntry(): void
    {
        $values = $this->getValues();

        $this->db->transaction(function (Connection $db) use ($values) {
            $id = $this->queryRoleId($db, $this->getIdentifier());

            $db->prepexec(
                (new Update())
                    ->table('icingaweb_role')
                    ->set([
                        'parent_id'    => $this->queryRoleId($db, $values['parent']),
                        'name'         => $values['name'],
                        'unrestricted' => $values['unrestricted'] ? 'y' : 'n',
                        'mtime'        => (new DateTime())->getTimestamp() * 1000
                    ])
                    ->where(['id = ?' => $id])
            );

            $db->prepexec((new Delete())->from('icingaweb_role_user')->where(['role_id = ?' => $id]));
            $db->prepexec((new Delete())->from('icingaweb_role_group')->where(['role_id = ?' => $id]));
            $db->prepexec((new Delete())->from('icingaweb_role_permission')->where(['role_id = ?' => $id]));
            $db->prepexec((new Delete())->from('icingaweb_role_restriction')->where(['role_id = ?' => $id]));

            $this->insertChildTables($db, $id, $values);
        });
    }

    /**
     * Query the ID of a role
     *
     * @param Connection $db Database to operate on
     * @param ?string $name  Target role name
     *
     * @return ?int Target role ID or null
     */
    private function queryRoleId(Connection $db, ?string $name): ?int
    {
        if ($name !== null) {
            $role = Role::on($db)->filter(Filter::equal('name', $name))->columns('id')->first();

            if ($role) {
                return $role->id;
            }
        }

        return null;
    }

    /**
     * Populate icingaweb_role_* tables for a new role
     *
     * @param Connection $db Database to operate on
     * @param int $id        Role ID
     * @param array $values  Role data as from {@link getValues()}
     */
    private function insertChildTables(Connection $db, int $id, array $values): void
    {
        $permissions = StringHelper::trimSplit($values['permissions']);
        $refusals = StringHelper::trimSplit($values['refusals']);
        $permissionsAndRefusals = [];

        foreach (StringHelper::trimSplit($values['users']) as $user) {
            $db->prepexec(
                (new Insert())
                    ->into('icingaweb_role_user')
                    ->columns(['role_id', 'user_name'])
                    ->values([$id, $user])
            );
        }

        foreach (StringHelper::trimSplit($values['groups']) as $group) {
            $db->prepexec(
                (new Insert())
                    ->into('icingaweb_role_group')
                    ->columns(['role_id', 'group_name'])
                    ->values([$id, $group])
            );
        }

        foreach ([$permissions, $refusals] as $permissionsOrRefusals) {
            foreach ($permissionsOrRefusals as $permissionOrRefusal) {
                $permissionsAndRefusals[$permissionOrRefusal] = ['allowed' => 'n', 'denied' => 'n'];
            }
        }

        foreach ($permissions as $permission) {
            $permissionsAndRefusals[$permission]['allowed'] = 'y';
        }

        foreach ($refusals as $refusal) {
            $permissionsAndRefusals[$refusal]['denied'] = 'y';
        }

        foreach ($permissionsAndRefusals as $name => $authz) {
            $db->prepexec(
                (new Insert())
                    ->into('icingaweb_role_permission')
                    ->columns(['role_id', 'permission', 'allowed', 'denied'])
                    ->values([$id, $name, $authz['allowed'], $authz['denied']])
            );
        }

        foreach ($this->providedRestrictions as $restrictionList) {
            foreach ($restrictionList as $name => $_) {
                if (isset($values[$name])) {
                    $db->prepexec(
                        (new Insert())
                            ->into('icingaweb_role_restriction')
                            ->columns(['role_id', 'restriction', 'filter'])
                            ->values([$id, $name, $values[$name]])
                    );
                }
            }
        }
    }

    protected function deleteEntry(): void
    {
        $this->db->prepexec((new Delete())->from('icingaweb_role')->where(['name = ?' => $this->getIdentifier()]));
    }

    protected function collectRoles(): array
    {
        $roles = [];
        $name = $this->getIdentifier();

        if ($name === null) {
            foreach (Role::on($this->db)->orderBy('name')->columns('name') as $role) {
                $roles[$role->name] = $role->name;
            }
        } else {
            $query = (new Select())
                ->with(
                    (new Select())
                        ->from('icingaweb_role')
                        ->where(['name = ?' => $name])
                        ->columns(['id', 'parent_id'])
                        ->union( // handle circular relationships
                            (new Select())
                                ->from(['r' => 'icingaweb_role'])
                                ->join('rl', 'rl.id = r.parent_id')
                                ->columns(['r.id', 'r.parent_id'])
                        ),
                    'rl',
                    true
                )
                ->from('icingaweb_role')
                ->where(['id NOT IN ?' => (new Select())->from('rl')->columns('id')])
                ->orderBy('name')
                ->columns('name');

            foreach ($this->db->select($query) as $row) {
                $roles[$row->name] = $row->name;
            }
        }

        return $roles;
    }

    protected function onRenameSuccess(string $oldName, ?string $newName): void
    {
        // Already handled by the database schema
    }

    /**
     * Set the database where the roles are stored
     *
     * @param Connection $db
     *
     * @return $this
     */
    public function setDb(Connection $db): self
    {
        $this->db = $db;

        return $this;
    }
}
