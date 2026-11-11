<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Forms\Config\UserGroup;

use Icinga\Authentication\UserGroup\DbUserGroupBackend;
use Icinga\Data\Db\DbConnection;
use Icinga\Forms\Config\UserGroup\UserGroupForm;
use Icinga\Repository\RepositoryMode;
use Icinga\Test\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UserGroupFormTest extends BaseTestCase
{
    public const GROUP_NAME = 'test_group';

    public const NEW_GROUP_NAME = 'new_group';

    #[DataProvider('mysqlDb')]
    public function testInsertModeInsertsNewGroup($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Insert);
        $form->populate(['group_name' => static::NEW_GROUP_NAME]);
        $form->ensureAssembled();

        $this->assertFalse(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_group')
                ->where('name', static::NEW_GROUP_NAME)
                ->hasResult(),
        );

        $form->exposeOnSuccess();

        $this->assertTrue(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_group')
                ->where('name', static::NEW_GROUP_NAME)
                ->hasResult(),
        );
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeUpdatesGroupName($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::GROUP_NAME);
        $form->populate(['group_name' => static::NEW_GROUP_NAME]);
        $form->ensureAssembled();

        $this->assertFalse(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_group')
                ->where('name', static::NEW_GROUP_NAME)
                ->hasResult(),
        );

        $form->exposeOnSuccess();

        $this->assertFalse(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_group')
                ->where('name', static::GROUP_NAME)
                ->hasResult(),
        );

        $this->assertTrue(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_group')
                ->where('name', static::NEW_GROUP_NAME)
                ->hasResult(),
        );
    }

    #[DataProvider('mysqlDb')]
    public function testDeleteModeDeletesGroup($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Delete, static::GROUP_NAME);
        $form->ensureAssembled();

        $this->assertTrue(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_group')
                ->where('name', static::GROUP_NAME)
                ->hasResult(),
        );

        $form->exposeOnSuccess();

        $this->assertFalse(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_group')
                ->where('name', static::GROUP_NAME)
                ->hasResult(),
        );
    }

    protected function createForm($db, RepositoryMode $mode, ?string $identifier = null): UserGroupForm
    {
        $this->setupDbProvider($db);
        $this->setUpGroupTables($db);

        $form = new class (new DbUserGroupBackend($db), $mode, $identifier) extends UserGroupForm {
            public function exposeOnSuccess(): void
            {
                $this->onSuccess();
            }
        };

        $form->disableCsrfCounterMeasure();

        return $form;
    }

    protected function setUpGroupTables(DbConnection $db): void
    {
        $createGroup = <<<'SQL'
CREATE TABLE `icingaweb_group`(
  `id`     int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`   varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent` int(10) unsigned NULL DEFAULT NULL,
  `ctime`  timestamp NULL DEFAULT NULL,
  `mtime`  timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
SQL;

        // Deleting a group also clears its memberships, so this table is required.
        $createMembership = <<<'SQL'
CREATE TABLE `icingaweb_group_membership`(
  `group_id` int(10) unsigned NOT NULL,
  `username` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ctime`    timestamp NULL DEFAULT NULL,
  `mtime`    timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
SQL;

        $db->getDbAdapter()->exec($createGroup);
        $db->getDbAdapter()->exec($createMembership);

        $db->getDbAdapter()->insert('icingaweb_group', ['name' => static::GROUP_NAME]);
    }
}
