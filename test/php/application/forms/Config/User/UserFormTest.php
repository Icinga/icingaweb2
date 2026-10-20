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
    /** @var string */
    public const USER_NAME = 'icingaadmin';

    /** @var string */
    public const NEW_USER_NAME = 'new_user';

    /** @var string */
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
    public function testInsertModeInsertsNewUser(DbConnection|string $db): void
    {
        $form = $this->createForm($db, RepositoryMode::Insert);
        $values = [
            'is_active' => '1',
            'user_name' => static::NEW_USER_NAME,
            'password'  => 'correct horse battery staple',
        ];

        $this->assertFalse(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::NEW_USER_NAME)
                ->hasResult(),
        );

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/add'))->withParsedBody([
                'uid'        => 'repo_form_user',
                'submit_add' => 'Add',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertTrue($form->isValid());

        $this->assertTrue(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::NEW_USER_NAME)
                ->hasResult(),
        );
    }

    #[DataProvider('mysqlDb')]
    public function testInsertModeAppliesPasswordPolicy(DbConnection|string $db): void
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
    public function testUpdateModeUpdatesUserName(DbConnection|string $db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $values = [
            'is_active' => '1',
            'user_name' => static::NEW_USER_NAME,
        ];

        $this->assertFalse(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::NEW_USER_NAME)
                ->hasResult(),
        );

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/edit'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'submit_update' => 'Save',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertTrue($form->isValid());

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
    public function testUpdateModeUpdatesPassword(DbConnection|string $db): void
    {
        $newPassword = 'correct horse battery staple';

        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $values = [
            'is_active' => '1',
            'user_name' => static::USER_NAME,
            'password'  => $newPassword,
        ];

        $this->assertFalse(password_verify(
            $newPassword,
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/edit'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'submit_update' => 'Save',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertTrue($form->isValid());

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
    public function testUpdateModeKeepsPasswordWhenLeftEmpty(DbConnection|string $db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $values = [
            'is_active' => '1',
            'user_name' => static::USER_NAME,
            'password'  => '',
        ];

        $this->assertTrue(password_verify(
            static::CURRENT_PASSWORD,
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/edit'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'submit_update' => 'Save',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertTrue($form->isValid());

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
    public function testUpdateModeKeepsPasswordWhenLeftEmptyUnderRestrictivePolicy(DbConnection|string $db): void
    {
        $this->usePolicy(CommonPasswordPolicy::class);

        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $values = [
            'is_active' => '1',
            'user_name' => static::USER_NAME,
            'password'  => '',
        ];

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/edit'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'submit_update' => 'Save',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());

        // An empty password leaves the stored one untouched, so there is nothing for
        // the policy to reject. Enforcing it here would make every other change to a
        // user impossible without also assigning a new password.
        $this->assertTrue($form->isValid());
        $this->assertEmpty($form->getElement('password')->getMessages());

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
    public function testUpdateModeKeepsPasswordWhenOnlyWhitespaceIsSubmitted(DbConnection|string $db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $values = [
            'user_name' => static::USER_NAME,
            'password'  => " \t\r\n ",
        ];

        $query = $db->select()
            ->columns(['password_hash'])
            ->from('icingaweb_user')
            ->where('name', static::USER_NAME);
        $hash = $query->fetchOne();

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/edit'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'submit_update' => 'Save',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertTrue($form->isValid());

        $this->assertSame($hash, $query->fetchOne());
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeIgnoresWhitespaceUnderRestrictivePolicy(DbConnection|string $db): void
    {
        $this->usePolicy(CommonPasswordPolicy::class);

        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $values = [
            'user_name' => static::USER_NAME,
            'password'  => '   ',
        ];

        $query = $db->select()
            ->columns(['password_hash'])
            ->from('icingaweb_user')
            ->where('name', static::USER_NAME);
        $hash = $query->fetchOne();

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/edit'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'submit_update' => 'Save',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertTrue($form->isValid());

        $this->assertSame($hash, $query->fetchOne());
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeAcceptsZeroAsPassword(DbConnection|string $db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $values = [
            'user_name' => static::USER_NAME,
            'password'  => '0',
        ];

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/edit'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'submit_update' => 'Save',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertTrue($form->isValid());

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
    public function testUpdateModeAppliesPasswordPolicy(DbConnection|string $db): void
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
    public function testInsertModeInsertsUserWhenPolicyFailsToLoad(DbConnection|string $db): void
    {
        // A configured policy that no registered hook provides makes loadConfigured() throw.
        Config::app()->setSection(
            PasswordPolicyHook::CONFIG_SECTION,
            [PasswordPolicyHook::CONFIG_KEY => 'missing/policy'],
        );

        $form = $this->createForm($db, RepositoryMode::Insert);
        $values = [
            'is_active' => '1',
            'user_name' => static::NEW_USER_NAME,
            'password'  => 'correct horse battery staple',
        ];

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/add'))->withParsedBody([
                'uid'        => 'repo_form_user',
                'submit_add' => 'Add',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());

        // The admin form stays unrestricted when the policy cannot be loaded.
        $this->assertTrue($form->isValid());

        $this->assertTrue(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::NEW_USER_NAME)
                ->hasResult(),
        );
    }

    #[DataProvider('mysqlDb')]
    public function testInsertModeIsRejectedWhenPolicyFailsToLoadAndPasswordIsNoString(DbConnection|string $db): void
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
            'password'  => ['correct horse battery staple'],
        ]);
        $form->ensureAssembled();

        $this->assertFalse($form->isValid());
        $this->assertSame('Password must be a string', $form->getElement('password')->getMessages()[0]);
    }

    #[DataProvider('mysqlDb')]
    public function testUpdateModeUpdatesPasswordWhenPolicyFailsToLoad(DbConnection|string $db): void
    {
        Config::app()->setSection(
            PasswordPolicyHook::CONFIG_SECTION,
            [PasswordPolicyHook::CONFIG_KEY => 'missing/policy'],
        );

        $newPassword = 'correct horse battery staple';

        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $values = [
            'is_active' => '1',
            'user_name' => static::USER_NAME,
            'password'  => $newPassword,
        ];

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/edit'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'submit_update' => 'Save',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());

        // The admin form stays unrestricted when the policy cannot be loaded.
        $this->assertTrue($form->isValid());

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
    public function testUpdateModeDeactivatesUser(DbConnection|string $db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $values = [
            'is_active' => '0',
            'user_name' => static::USER_NAME,
        ];

        $this->assertEquals(1, $db->select()
            ->columns(['active'])
            ->from('icingaweb_user')
            ->where('name', static::USER_NAME)
            ->fetchOne());

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/edit'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'submit_update' => 'Save',
            ] + $values),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertTrue($form->isValid());

        $this->assertEquals(0, $db->select()
            ->columns(['active'])
            ->from('icingaweb_user')
            ->where('name', static::USER_NAME)
            ->fetchOne());
    }

    #[DataProvider('mysqlDb')]
    public function testDeleteModeDeletesUser(DbConnection|string $db): void
    {
        $form = $this->createForm($db, RepositoryMode::Delete, static::USER_NAME);

        $this->assertTrue(
            $db->select()
                ->columns(['*'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->hasResult(),
        );

        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/user/remove'))->withParsedBody([
                'uid'           => 'repo_form_user',
                'submit_remove' => 'Confirm Removal',
            ]),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertTrue($form->isValid());

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
    public function testUpdateRequestPrefillsTheStoredUser(DbConnection|string $db): void
    {
        $form = $this->createForm($db, RepositoryMode::Update, static::USER_NAME);
        $form->handleRequest(new ServerRequest('GET', '/icingaweb2/user/edit'));

        /** @var CheckboxElement $isActive */
        $isActive = $form->getElement('is_active');

        // PostgreSQL returns the smallint column as int, which must still check the box.
        $this->assertTrue($isActive->isChecked());
        $this->assertSame(static::USER_NAME, $form->getValue('user_name'));
        $this->assertNull($form->getValue('password'));
    }

    #[DataProvider('mysqlDb')]
    #[DataProvider('pgsqlDb')]
    public function testUpdateSubmitKeepsUntouchedUserActive(DbConnection|string $db): void
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

    protected function createForm(DbConnection|string $db, RepositoryMode $mode, ?string $identifier = null): UserForm
    {
        $this->setupDbProvider($db);
        $this->setUpUserTable($db);

        $form = new UserForm(new DbUserBackend($db), $mode, $identifier);

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
     * @param class-string<PasswordPolicyHook> $policyClass The policy to enable
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
