<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Forms\Account;

use Icinga\Application\Config;
use Icinga\Application\Hook;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;
use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use Icinga\Authentication\User\DbUserBackend;
use Icinga\Data\Db\DbConnection;
use Icinga\Forms\Account\ChangePasswordForm;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use PHPUnit\Framework\Attributes\DataProvider;

class ChangePasswordFormTest extends BaseTestCase
{
    public const USER_NAME = 'icingaadmin';

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
    public function testInvalidOldPasswordIsRejected($db): void
    {
        $form = $this->createForm($db, 'wrong_password', 'icinga123', 'icinga123');

        $this->assertFalse($form->isValid());
        $this->assertSame(
            'Old password is invalid',
            $form->getElement(ChangePasswordForm::OLD_PASSWORD_ELEMENT_NAME)->getMessages()[0],
        );
    }

    #[DataProvider('mysqlDb')]
    public function testMismatchedPasswordConfirmationIsRejected($db): void
    {
        $form = $this->createForm($db, static::CURRENT_PASSWORD, 'icinga123', 'icinga456');

        $this->assertFalse($form->isValid());
        $this->assertSame(
            'The passwords do not match',
            $form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME . '_confirmation')->getMessages()[0],
        );
    }

    #[DataProvider('mysqlDb')]
    public function testNonStringNewPasswordIsRejectedByConfirmation($db): void
    {
        $form = $this->createForm($db, static::CURRENT_PASSWORD, '', 'icinga123');

        $this->assertFalse($form->isValid());
        $this->assertSame(
            'Password must be string',
            $form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME . '_confirmation')->getMessages()[0],
        );
    }

    #[DataProvider('mysqlDb')]
    public function testValidInputChangesPassword($db): void
    {
        $newPassword = 'icinga123';
        $form = $this->createForm($db, static::CURRENT_PASSWORD, $newPassword, $newPassword);

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
    public function testPasswordViolatingTheConfiguredPolicyIsRejected($db): void
    {
        $this->usePolicy(CommonPasswordPolicy::class);
        $form = $this->createForm($db, static::CURRENT_PASSWORD, 'test', 'test');

        $this->assertFalse($form->isValid());
        $this->assertNotEmpty($form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME)->getMessages());
    }

    #[DataProvider('mysqlDb')]
    public function testPasswordSatisfyingTheConfiguredPolicyIsAccepted($db): void
    {
        $this->usePolicy(CommonPasswordPolicy::class);
        $form = $this->createForm($db, static::CURRENT_PASSWORD, 'Testpassword123!', 'Testpassword123!');

        $this->assertTrue($form->isValid());
    }

    #[DataProvider('mysqlDb')]
    public function testPasswordChangeIsRejectedWhenPolicyFailsToLoad($db): void
    {
        Config::app()->setSection(
            PasswordPolicyHook::CONFIG_SECTION,
            [PasswordPolicyHook::CONFIG_KEY => 'missing/policy'],
        );

        $newPassword = 'icinga123';
        $form = $this->createForm($db, static::CURRENT_PASSWORD, $newPassword, $newPassword);

        $this->assertFalse($form->isValid());
        $this->assertNotEmpty($form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME)->getMessages());
        $this->assertTrue(password_verify(
            static::CURRENT_PASSWORD,
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));
    }

    protected function createForm(
        $db,
        string $oldPassword,
        string $newPassword,
        string $newPasswordConfirmation,
    ): ChangePasswordForm {
        $this->setupDbProvider($db);
        $this->setUpUserTable($db);

        $form = new class (new DbUserBackend($db), new User(static::USER_NAME)) extends ChangePasswordForm {
            public function exposeOnSuccess(): void
            {
                $this->onSuccess();
            }
        };
        $form->populate([
            ChangePasswordForm::OLD_PASSWORD_ELEMENT_NAME                   => $oldPassword,
            ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME                   => $newPassword,
            ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME . '_confirmation' => $newPasswordConfirmation,
        ]);
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        return $form;
    }

    protected function setUpUserTable(DbConnection $db): void
    {
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
