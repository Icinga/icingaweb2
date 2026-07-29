<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Forms\Security;

use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Reducible;
use Icinga\Data\Updatable;
use Icinga\Forms\Security\RoleForm;
use Icinga\Repository\Repository;
use Icinga\Repository\RepositoryMode;
use Icinga\Test\BaseTestCase;

/**
 * Tests for {@see RoleForm}
 */
class RoleFormTest extends BaseTestCase
{
    /**
     * Create a role form providing a fixed set of privileges
     *
     * The form is populated first and assembled afterwards, just like when
     * handling a request. $values is what a browser would have submitted.
     *
     * @param RepositoryMode $mode
     * @param array<string, mixed> $values
     * @param ?string $identifier
     * @param array<int, object> $roles The roles the repository provides
     *
     * @return RoleForm
     */
    private function createForm(
        RepositoryMode $mode,
        array $values = [],
        ?string $identifier = null,
        array $roles = []
    ): RoleForm {
        $form = new class ($this->createRepository($roles), $mode, $identifier) extends RoleForm {
            public static function collectProvidedPrivileges(): array
            {
                return [
                    [
                        'application' => [
                            'application/announcements' => ['description' => 'Allow to manage announcements'],
                            'application/log'           => ['description' => 'Allow to view the application log'],
                            'user/password-change'      => ['description' => 'Allow password changes in preferences'],
                        ],
                        'mymodule'    => [
                            'module/mymodule' => ['isUsagePerm' => true, 'label' => 'General Module Access'],
                            'mymodule/*'      => ['isFullPerm' => true, 'label' => 'Full Module Access'],
                            'mymodule/read'   => ['description' => 'Allow to read'],
                        ],
                    ],
                    [
                        'application' => [
                            'application/share/users' => ['description' => 'Restrict which users to share with'],
                        ],
                        'mymodule'    => [
                            'mymodule/filter' => ['description' => 'Restrict what to see'],
                        ],
                    ],
                ];
            }

            public function exposeFetchEntry(): object|false
            {
                return $this->fetchEntry();
            }
        };

        $form->disableCsrfCounterMeasure();
        $form->populate($values);
        $form->ensureAssembled();

        return $form;
    }

    /**
     * Create a repository suitable for every mode the role form supports
     *
     * @param array<int, object> $roles The roles the repository provides
     *
     * @return Repository
     */
    private function createRepository(array $roles = []): Repository
    {
        return new class (new ArrayDatasource($roles)) extends Repository implements Extensible, Reducible, Updatable {
            /** @var array<string, array<int, string>> */
            protected $queryColumns = [
                'roles' => [
                    'name',
                    'parent',
                    'users',
                    'groups',
                    'permissions',
                    'refusals',
                    'unrestricted',
                    'application/share/users',
                    'mymodule/filter',
                ],
            ];

            public function insert($target, array $data): void
            {
            }

            public function update($target, array $data, ?Filter $filter = null): void
            {
            }

            public function delete($target, ?Filter $filter = null): void
            {
            }
        };
    }

    public function testGetValuesTurnsCheckedPermissionsIntoAPermissionList(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, [
            'application_elements' => ['applicationannouncements' => 'y'],
            'mymodule_elements'    => ['mymoduleread' => 'y'],
        ]);

        $this->assertSame('application/announcements,mymodule/read', $form->getValues()['permissions']);
    }

    public function testGetValuesIgnoresUncheckedPermissions(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, [
            'application_elements' => ['applicationannouncements' => 'y', 'applicationlog' => 'n'],
        ]);

        $this->assertSame('application/announcements', $form->getValues()['permissions']);
    }

    public function testGetValuesTurnsCheckedDenyTogglesIntoARefusalList(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, [
            'application_elements' => ['noapplicationlog' => 'y'],
            'mymodule_elements'    => ['nomymoduleread' => 'y'],
        ]);

        $this->assertSame('application/log,mymodule/read', $form->getValues()['refusals']);
    }

    public function testGetValuesAddsTheWildcardPermissionForAdministrativeAccess(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, [RoleForm::WILDCARD_NAME => 'y']);

        $this->assertSame('*', $form->getValues()['permissions']);
    }

    public function testGetValuesPreservesPermissionsHiddenByAdministrativeAccess(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, [
            RoleForm::WILDCARD_NAME => 'y',
            'application_elements'  => ['applicationannouncements' => 'y'],
        ]);

        $this->assertSame('*,application/announcements', $form->getValues()['permissions']);
    }

    public function testGetValuesImpliesGeneralModuleAccessForFullModuleAccess(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, ['mymodule_elements' => ['mymodule' => 'y']]);

        $this->assertSame('mymodule/*,module/mymodule', $form->getValues()['permissions']);
    }

    public function testGetValuesDoesNotAddGeneralModuleAccessTwice(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, [
            'mymodule_elements' => ['modulemymodule' => 'y', 'mymodule' => 'y'],
        ]);

        $this->assertSame('module/mymodule,mymodule/*', $form->getValues()['permissions']);
    }

    public function testGetValuesLiftsRestrictionsToTheTopLevel(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, [
            'application_elements' => ['applicationshareusers' => 'icingaadmin'],
            'mymodule_elements'    => ['mymodulefilter' => 'host=foo'],
        ]);
        $values = $form->getValues();

        $this->assertSame('icingaadmin', $values['application/share/users']);
        $this->assertSame('host=foo', $values['mymodule/filter']);
    }

    public function testGetValuesDropsTheFieldsetsAndTheWildcardElement(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, [
            RoleForm::WILDCARD_NAME => 'y',
            'application_elements'  => ['applicationannouncements' => 'y'],
        ]);
        $values = $form->getValues();

        $this->assertArrayNotHasKey(RoleForm::WILDCARD_NAME, $values);
        $this->assertArrayNotHasKey('application_elements', $values);
        $this->assertArrayNotHasKey('mymodule_elements', $values);
    }

    public function testGetValuesKeepsTheRolesBasicProperties(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, [
            'name'         => 'test',
            'parent'       => 'admins',
            'users'        => 'icingaadmin',
            'groups'       => 'support',
            'unrestricted' => '1',
        ]);
        $values = $form->getValues();

        $this->assertSame('test', $values['name']);
        $this->assertSame('admins', $values['parent']);
        $this->assertSame('icingaadmin', $values['users']);
        $this->assertSame('support', $values['groups']);
        $this->assertSame('1', $values['unrestricted']);
    }

    public function testGetValuesYieldsNullForEmptyPermissionsAndRefusals(): void
    {
        $form = $this->createForm(RepositoryMode::Insert);
        $values = $form->getValues();

        $this->assertNull($values['permissions']);
        $this->assertNull($values['refusals']);
    }

    public function testGetValuesYieldsNullForEmptyProperties(): void
    {
        $form = $this->createForm(RepositoryMode::Insert, [
            'name'                 => 'test',
            'parent'               => '',
            'users'                => '',
            'groups'               => '',
            'application_elements' => ['applicationshareusers' => ''],
        ]);
        $values = $form->getValues();

        $this->assertNull($values['parent']);
        $this->assertNull($values['users']);
        $this->assertNull($values['groups']);
        $this->assertNull($values['application/share/users']);
    }

    public function testGetValuesSkipsThePrivilegeTransformationInDeleteMode(): void
    {
        $form = $this->createForm(RepositoryMode::Delete, [], 'test');
        $values = $form->getValues();

        $this->assertArrayNotHasKey('permissions', $values);
        $this->assertArrayNotHasKey('refusals', $values);
    }

    public function testFetchEntryReturnsFalseIfTheRoleDoesNotExist(): void
    {
        $form = $this->createForm(RepositoryMode::Update, [], 'missing');

        $this->assertFalse($form->exposeFetchEntry());
    }

    public function testFetchEntryMapsTheRolesBasicProperties(): void
    {
        $role = (object) [
            'name'         => 'test',
            'parent'       => 'admins',
            'users'        => 'icingaadmin',
            'groups'       => 'support',
            'unrestricted' => '1',
        ];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('test', $entry->name);
        $this->assertSame('admins', $entry->parent);
        $this->assertSame('icingaadmin', $entry->users);
        $this->assertSame('support', $entry->groups);
        $this->assertSame('1', $entry->unrestricted);
    }

    public function testFetchEntryChecksTheElementsOfGrantedPermissions(): void
    {
        $role = (object) [
            'name'        => 'test',
            'parent'      => null,
            'permissions' => 'application/announcements,mymodule/read',
        ];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('y', $entry->application_elements['applicationannouncements']);
        $this->assertSame('y', $entry->mymodule_elements['mymoduleread']);
        $this->assertArrayNotHasKey('applicationlog', $entry->application_elements);
    }

    public function testFetchEntryChecksTheDenyTogglesOfRefusedPermissions(): void
    {
        $role = (object) ['name' => 'test', 'parent' => null, 'refusals' => 'application/log'];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('y', $entry->application_elements['noapplicationlog']);
        $this->assertArrayNotHasKey('applicationlog', $entry->application_elements);
    }

    public function testFetchEntryDetectsAdministrativeAccessAsFirstPermission(): void
    {
        $role = (object) ['name' => 'test', 'parent' => null, 'permissions' => '*,application/log'];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('y', $entry->{RoleForm::WILDCARD_NAME});
    }

    public function testFetchEntryDetectsAdministrativeAccessAsLastPermission(): void
    {
        $role = (object) ['name' => 'test', 'parent' => null, 'permissions' => 'application/log,*'];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('y', $entry->{RoleForm::WILDCARD_NAME});
    }

    public function testFetchEntryDetectsAdministrativeAccessAsMiddlePermission(): void
    {
        $role = (object) ['name' => 'test', 'parent' => null, 'permissions' => 'application/log,*,mymodule/read'];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('y', $entry->{RoleForm::WILDCARD_NAME});
    }

    public function testFetchEntryDoesNotMistakeAModuleWildcardForAdministrativeAccess(): void
    {
        $role = (object) ['name' => 'test', 'parent' => null, 'permissions' => 'mymodule/*'];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('n', $entry->{RoleForm::WILDCARD_NAME});
    }

    public function testFetchEntryMigratesLegacyPermissions(): void
    {
        $role = (object) ['name' => 'test', 'parent' => null, 'permissions' => 'admin,no-user/password-change'];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('y', $entry->application_elements['applicationannouncements']);
        $this->assertSame('y', $entry->application_elements['nouserpasswordchange']);
    }

    public function testFetchEntryOmitsGeneralModuleAccessForFullModuleAccess(): void
    {
        $role = (object) ['name' => 'test', 'parent' => null, 'permissions' => 'module/mymodule,mymodule/*'];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('y', $entry->mymodule_elements['mymodule']);
        $this->assertArrayNotHasKey('modulemymodule', $entry->mymodule_elements);
    }

    public function testFetchEntryCopiesRestrictionsIntoTheirFieldsets(): void
    {
        $role = (object) [
            'name'                    => 'test',
            'parent'                  => null,
            'application/share/users' => 'icingaadmin',
            'mymodule/filter'         => 'host=foo',
        ];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('icingaadmin', $entry->application_elements['applicationshareusers']);
        $this->assertSame('host=foo', $entry->mymodule_elements['mymodulefilter']);
    }

    public function testFetchEntryYieldsNoFieldsetsForARoleWithoutPrivileges(): void
    {
        $role = (object) ['name' => 'test', 'parent' => null];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('n', $entry->{RoleForm::WILDCARD_NAME});
        $this->assertObjectNotHasProperty('application_elements', $entry);
        $this->assertObjectNotHasProperty('mymodule_elements', $entry);
    }

    public function testFetchEntryTrimsSpacesBetweenPermissions(): void
    {
        $role = (object) [
            'name'        => 'test',
            'parent'      => null,
            'permissions' => 'application/announcements , mymodule/read',
        ];
        $form = $this->createForm(RepositoryMode::Update, [], 'test', [$role]);
        $entry = $form->exposeFetchEntry();

        $this->assertSame('y', $entry->application_elements['applicationannouncements']);
        $this->assertSame('y', $entry->mymodule_elements['mymoduleread']);
        $this->assertArrayNotHasKey('applicationlog', $entry->application_elements);
    }
}
