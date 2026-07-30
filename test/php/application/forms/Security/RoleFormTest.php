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
     *
     * @return RoleForm
     */
    private function createForm(RepositoryMode $mode, array $values = [], ?string $identifier = null): RoleForm
    {
        $form = new class ($this->createRepository(), $mode, $identifier) extends RoleForm {
            public static function collectProvidedPrivileges(): array
            {
                return [
                    [
                        'application' => [
                            'application/announcements' => ['description' => 'Allow to manage announcements'],
                            'application/log'           => ['description' => 'Allow to view the application log'],
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
        };

        $form->disableCsrfCounterMeasure();
        $form->populate($values);
        $form->ensureAssembled();

        return $form;
    }

    /**
     * Create a repository suitable for every mode the role form supports
     *
     * @param array<int, array<string, mixed>> $roles The roles the repository provides
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
}
