<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Forms\Config\User;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Hook;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;
use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use Icinga\Authentication\User\DbUserBackend;
use Icinga\Data\Db\DbConnection;
use Icinga\Forms\Config\User\UserForm;
use Icinga\Repository\RepositoryMode;
use Icinga\Test\BaseTestCase;
use ipl\Html\FormElement\CheckboxElement;
use PHPUnit\Framework\Attributes\DataProvider;

class UserFormTest extends BaseTestCase
{
    public const USER_NAME = 'icingaadmin';

    public const NEW_USER_NAME = 'new_user';

    public const CURRENT_PASSWORD = 'icinga';

    public function setUp(): void
    {
        parent::setUp();

        Hook::clean();
        AnyPasswordPolicy::register();
        Config::app()->removeSection(PasswordPolicyHook::CONFIG_SECTION);
    }

    public function tearDown(): void
    {
        Hook::clean();
        Config::app()->removeSection(PasswordPolicyHook::CONFIG_SECTION);

        parent::tearDown();
    }

    #[DataProvider('mysqlDb')]
    public function testInsertModeInsertsNewUser($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Insert);
        $form->populate([
            'is_active' => '1',
            'user_name' => static::NEW_USER_NAME,
            'password'  => 'new_password',
        ]);
        $form->ensureAssembled();

        $this->assertFalse(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::NEW_USER_NAME)
                ->hasResult(),
        );

        $this->assertTrue($form->isValid());

        $form->exposeOnSuccess();

        $this->assertTrue(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::NEW_USER_NAME)
                ->hasResult(),
        );
    }

    #[DataProvider('mysqlDb')]
    public function testInsertModeAppliesPasswordPolicy($db): void
    {
        $this->usePolicy(CommonPasswordPolicy::class);

        $form = $this->createForm($db, RepositoryMode::Insert);
        $form->populate([
            'is_active' => '1',
            'user_name' => static::NEW_USER_NAME,
            'password'  => 'test',
        ]);
        $form->ensureAssembled();

        $this->assertFalse($form->isValid());
        $this->assertNotEmpty($form->getElement('password')->getMessages());
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeUpdatesUserName($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->populate([
            'is_active' => '1',
            'user_name' => static::NEW_USER_NAME,
        ]);
        $form->ensureAssembled();

        $this->assertFalse(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::NEW_USER_NAME)
                ->hasResult(),
        );

        $this->assertTrue($form->isValid());

        $form->exposeOnSuccess();

        $this->assertFalse(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->hasResult(),
        );

        $this->assertTrue(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::NEW_USER_NAME)
                ->hasResult(),
        );
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeUpdatesPassword($db): void
    {
        $newPassword = 'new_password';

        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->populate([
            'is_active' => '1',
            'user_name' => static::USER_NAME,
            'password'  => $newPassword,
        ]);
        $form->ensureAssembled();

        $this->assertFalse(password_verify(
            $newPassword,
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));

        $this->assertTrue($form->isValid());

        $form->exposeOnSuccess();

        $this->assertTrue(password_verify(
            $newPassword,
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeKeepsPasswordWhenLeftEmpty($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->populate([
            'is_active' => '1',
            'user_name' => static::USER_NAME,
            'password'  => '',
        ]);
        $form->ensureAssembled();

        $this->assertTrue(password_verify(
            static::CURRENT_PASSWORD,
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));

        $this->assertTrue($form->isValid());

        $form->exposeOnSuccess();

        $this->assertTrue(password_verify(
            static::CURRENT_PASSWORD,
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeKeepsPasswordWhenLeftEmptyUnderRestrictivePolicy($db): void
    {
        $this->usePolicy(CommonPasswordPolicy::class);

        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->populate([
            'is_active' => '1',
            'user_name' => static::USER_NAME,
            'password'  => '',
        ]);
        $form->ensureAssembled();

        // An empty password leaves the stored one untouched, so there is nothing for
        // the policy to reject. Enforcing it here would make every other change to a
        // user impossible without also assigning a new password.
        $this->assertTrue($form->isValid());
        $this->assertEmpty($form->getElement('password')->getMessages());

        $form->exposeOnSuccess();

        $this->assertTrue(password_verify(
            static::CURRENT_PASSWORD,
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeKeepsPasswordWhenOnlyWhitespaceIsSubmitted($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->populate([
            'user_name' => static::USER_NAME,
            'password'  => " \t\r\n ",
        ]);
        $form->ensureAssembled();

        $query = $db->select()
            ->columns(['password_hash'])
            ->from('icingaweb_user')
            ->where('name', static::USER_NAME);
        $hash = $query->fetchOne();

        $this->assertTrue($form->isValid());
        $form->exposeOnSuccess();

        $this->assertSame($hash, $query->fetchOne());
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeIgnoresWhitespaceUnderRestrictivePolicy($db): void
    {
        $this->usePolicy(CommonPasswordPolicy::class);

        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->populate([
            'user_name' => static::USER_NAME,
            'password'  => '   ',
        ]);
        $form->ensureAssembled();

        $query = $db->select()
            ->columns(['password_hash'])
            ->from('icingaweb_user')
            ->where('name', static::USER_NAME);
        $hash = $query->fetchOne();

        $this->assertTrue($form->isValid());
        $form->exposeOnSuccess();

        $this->assertSame($hash, $query->fetchOne());
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeAcceptsZeroAsPassword($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->populate([
            'user_name' => static::USER_NAME,
            'password'  => '0',
        ]);
        $form->ensureAssembled();

        $this->assertTrue($form->isValid());
        $form->exposeOnSuccess();

        $this->assertTrue(password_verify(
            '0',
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeAppliesPasswordPolicy($db): void
    {
        $this->usePolicy(CommonPasswordPolicy::class);

        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->populate([
            'is_active' => '1',
            'user_name' => static::USER_NAME,
            'password'  => 'test',
        ]);
        $form->ensureAssembled();

        $this->assertFalse($form->isValid());
        $this->assertNotEmpty($form->getElement('password')->getMessages());
    }

    #[DataProvider('mysqlDb')]
    public function testInsertModeInsertsUserWhenPolicyFailsToLoad($db): void
    {
        // A configured policy that no registered hook provides makes loadConfigured() throw
        Config::app()->setSection(
            PasswordPolicyHook::CONFIG_SECTION,
            [PasswordPolicyHook::CONFIG_KEY => 'missing/policy'],
        );

        $form = $this->createForm($db, RepositoryMode::Insert);
        $form->populate([
            'is_active' => '1',
            'user_name' => static::NEW_USER_NAME,
            'password'  => 'new_password',
        ]);
        $form->ensureAssembled();

        // The admin form stays unrestricted when the policy cannot be loaded
        $this->assertTrue($form->isValid());

        $form->exposeOnSuccess();

        $this->assertTrue(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::NEW_USER_NAME)
                ->hasResult(),
        );
    }

    #[DataProvider('mysqlDb')]
    public function testInsertModeIsRejectedWhenPolicyFailsToLoadAndPasswordIsNoString($db): void
    {
        Config::app()->setSection(
            PasswordPolicyHook::CONFIG_SECTION,
            [PasswordPolicyHook::CONFIG_KEY => 'missing/policy'],
        );

        $form = $this->createForm($db, RepositoryMode::Insert);

        // The required validator accepts a non-empty array, so leaving the admin
        // form unrestricted must still not let a non-string reach password_hash().
        $form->populate([
            'is_active' => '1',
            'user_name' => static::NEW_USER_NAME,
            'password'  => ['new_password'],
        ]);
        $form->ensureAssembled();

        $this->assertFalse($form->isValid());
        $this->assertSame('Password must be a string', $form->getElement('password')->getMessages()[0]);
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeUpdatesPasswordWhenPolicyFailsToLoad($db): void
    {
        Config::app()->setSection(
            PasswordPolicyHook::CONFIG_SECTION,
            [PasswordPolicyHook::CONFIG_KEY => 'missing/policy'],
        );

        $newPassword = 'new_password';

        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->populate([
            'is_active' => '1',
            'user_name' => static::USER_NAME,
            'password'  => $newPassword,
        ]);
        $form->ensureAssembled();

        // The admin form stays unrestricted when the policy cannot be loaded
        $this->assertTrue($form->isValid());

        $form->exposeOnSuccess();

        $this->assertTrue(password_verify(
            $newPassword,
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeDeactivatesUser($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->populate([
            'is_active' => '0',
            'user_name' => static::USER_NAME,
        ]);
        $form->ensureAssembled();

        $this->assertEquals(1, $db->select()
            ->columns(['active'])
            ->from('icingaweb_user')
            ->where('name', static::USER_NAME)
            ->fetchOne());

        $this->assertTrue($form->isValid());

        $form->exposeOnSuccess();

        $this->assertEquals(0, $db->select()
            ->columns(['active'])
            ->from('icingaweb_user')
            ->where('name', static::USER_NAME)
            ->fetchOne());
    }

    #[DataProvider('mysqlDb')]
    public function testDeleteModeDeletesUser($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Delete, static::USER_NAME);
        $form->ensureAssembled();

        $this->assertTrue(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->hasResult(),
        );

        $this->assertTrue($form->isValid());

        $form->exposeOnSuccess();

        $this->assertFalse(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->hasResult(),
        );
    }

    #[DataProvider('mysqlDb')]
    #[DataProvider('pgsqlDb')]
    public function testUpdateRequestPrefillsTheStoredUser($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->handleRequest(new ServerRequest('GET', '/icingaweb2/user/edit'));

        /** @var CheckboxElement $isActive */
        $isActive = $form->getElement('is_active');

        // PostgreSQL returns the smallint column as int, which must still check the box
        $this->assertTrue($isActive->isChecked());
        $this->assertSame(static::USER_NAME, $form->getValue('user_name'));
        $this->assertNull($form->getValue('password'));
    }

    #[DataProvider('mysqlDb')]
    #[DataProvider('pgsqlDb')]
    public function testUpdateSubmitKeepsUntouchedUserActive($db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/edit'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'is_active'     => '1',
                'user_name'     => static::USER_NAME,
                'password'      => '',
                'submit_update' => 'Save',
            ]),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertTrue($form->isValid());
        $this->assertEquals(1, $db->select()
            ->columns(['active'])
            ->from('icingaweb_user')
            ->where('name', static::USER_NAME)
            ->fetchOne());
    }

    protected function createForm($db, RepositoryMode $mode, ?string $identifier = null): UserForm
    {
        $this->setupDbProvider($db);
        $this->setUpUserTable($db);

        $form = new class (new DbUserBackend($db), $mode, $identifier) extends UserForm {
            public function exposeOnSuccess(): void
            {
                $this->onSuccess();
            }
        };

        $form->disableCsrfCounterMeasure();

        return $form;
    }

    protected function setUpUserTable(DbConnection $db): void
    {
        if ($db->getDbType() === 'pgsql') {
            $createTable = <<<'SQL'
CREATE OR REPLACE FUNCTION unix_timestamp(timestamp with time zone) RETURNS bigint AS '
        SELECT EXTRACT(EPOCH FROM $1)::bigint AS result
' LANGUAGE sql;

CREATE TABLE "icingaweb_user" (
  "name"          character varying(254) NOT NULL PRIMARY KEY,
  "active"        smallint NOT NULL,
  "password_hash" bytea NOT NULL,
  "ctime"         timestamp NULL DEFAULT NULL,
  "mtime"         timestamp NULL DEFAULT NULL
);
SQL;
        } else {
            $createTable = <<<'SQL'
CREATE TABLE `icingaweb_user`(
  `name`          varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active`        tinyint(1) NOT NULL,
  `password_hash` varbinary(255) NOT NULL,
  `ctime`         timestamp NULL DEFAULT NULL,
  `mtime`         timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
SQL;
        }

        $db->getDbAdapter()->exec($createTable);

        $db->getDbAdapter()->insert('icingaweb_user', [
            'name'          => static::USER_NAME,
            'active'        => 1,
            'password_hash' => password_hash(static::CURRENT_PASSWORD, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * @param class-string $policyClass The policy to enable
     *
     * @return void
     */
    protected function usePolicy(string $policyClass): void
    {
        Hook::clean();
        $policyClass::register();

        Config::app()->setSection(
            PasswordPolicyHook::CONFIG_SECTION,
            [PasswordPolicyHook::CONFIG_KEY => (new $policyClass())->getCanonicalName()],
        );
    }
}
