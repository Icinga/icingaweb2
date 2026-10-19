<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Forms\Account;

use GuzzleHttp\Psr7\ServerRequest;
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
use Tests\Icinga\Lib\Authentication\OldPasswordRecordingPolicy;

class ChangePasswordFormTest extends BaseTestCase
{
    /** @var string */
    public const USER_NAME = 'icingaadmin';

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
    public function testInvalidOldPasswordIsRejected(DbConnection|string $db): void
    {
        $form = $this->createForm(
            $db,
            'wrong_password',
            'correct horse battery staple',
            'correct horse battery staple',
        );

        $this->assertFalse($form->isValid());
        $this->assertSame(
            'Old password is invalid',
            $form->getElement(ChangePasswordForm::OLD_PASSWORD_ELEMENT_NAME)->getMessages()[0],
        );
    }

    #[DataProvider('mysqlDb')]
    public function testNonStringOldPasswordIsRejected(DbConnection|string $db): void
    {
        $form = $this->createForm(
            $db,
            [static::CURRENT_PASSWORD],
            'correct horse battery staple',
            'correct horse battery staple',
        );

        $this->assertFalse($form->isValid());
        $this->assertSame(
            'Password must be a string',
            $form->getElement(ChangePasswordForm::OLD_PASSWORD_ELEMENT_NAME)->getMessages()[0],
        );
    }

    #[DataProvider('mysqlDb')]
    public function testMismatchedPasswordConfirmationIsRejected(DbConnection|string $db): void
    {
        $form = $this->createForm(
            $db,
            static::CURRENT_PASSWORD,
            'correct horse battery staple',
            'different horse battery staple',
        );

        $this->assertFalse($form->isValid());
        $this->assertSame(
            'The passwords do not match',
            $form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME . '_confirmation')->getMessages()[0],
        );
    }

    #[DataProvider('mysqlDb')]
    public function testNonStringNewPasswordIsRejectedByConfirmation(DbConnection|string $db): void
    {
        $form = $this->createForm(
            $db,
            static::CURRENT_PASSWORD,
            ['correct horse battery staple'],
            'correct horse battery staple',
        );

        $this->assertFalse($form->isValid());
        $this->assertSame(
            'Password must be a string',
            $form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME . '_confirmation')->getMessages()[0],
        );
    }

    #[DataProvider('mysqlDb')]
    public function testNonStringPasswordConfirmationIsRejected(DbConnection|string $db): void
    {
        $form = $this->createForm(
            $db,
            static::CURRENT_PASSWORD,
            'correct horse battery staple',
            ['correct horse battery staple'],
        );

        $this->assertFalse($form->isValid());
        $this->assertSame(
            'Password must be a string',
            $form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME . '_confirmation')->getMessages()[0],
        );
    }

    #[DataProvider('mysqlDb')]
    public function testEmptyNewPasswordWithConfirmationIsRejected(DbConnection|string $db): void
    {
        $form = $this->createForm($db, static::CURRENT_PASSWORD, '', 'correct horse battery staple');

        $this->assertFalse($form->isValid());
        $this->assertNotEmpty($form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME)->getMessages());
        $this->assertEmpty(
            $form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME . '_confirmation')->getMessages(),
        );
    }

    #[DataProvider('mysqlDb')]
    public function testValidInputChangesPassword(DbConnection|string $db): void
    {
        $this->setupDbProvider($db);
        $this->setUpUserTable($db);

        $newPassword = 'correct horse battery staple';
        $form = new ChangePasswordForm(new DbUserBackend($db), new User(static::USER_NAME));
        $form->disableCsrfCounterMeasure();
        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/account'))->withParsedBody([
                'uid'                                                           => 'form_change_password',
                ChangePasswordForm::OLD_PASSWORD_ELEMENT_NAME                   => static::CURRENT_PASSWORD,
                ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME                   => $newPassword,
                ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME . '_confirmation' => $newPassword,
                'submit'                                                        => 'Update Account',
            ]),
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
    public function testOldPasswordIsPassedToThePolicy(DbConnection|string $db): void
    {
        OldPasswordRecordingPolicy::$oldPassword = null;
        $this->usePolicy(OldPasswordRecordingPolicy::class);

        $form = $this->createForm(
            $db,
            static::CURRENT_PASSWORD,
            'correct horse battery staple',
            'correct horse battery staple',
        );

        $this->assertTrue($form->isValid());
        $this->assertSame(static::CURRENT_PASSWORD, OldPasswordRecordingPolicy::$oldPassword);
    }

    #[DataProvider('mysqlDb')]
    public function testPasswordViolatingTheConfiguredPolicyIsRejected(DbConnection|string $db): void
    {
        $this->usePolicy(CommonPasswordPolicy::class);
        $form = $this->createForm($db, static::CURRENT_PASSWORD, 'test', 'test');

        $this->assertFalse($form->isValid());
        $this->assertNotEmpty($form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME)->getMessages());
    }

    #[DataProvider('mysqlDb')]
    public function testPasswordSatisfyingTheConfiguredPolicyIsAccepted(DbConnection|string $db): void
    {
        $this->usePolicy(CommonPasswordPolicy::class);
        $form = $this->createForm(
            $db,
            static::CURRENT_PASSWORD,
            'Correct horse battery staple 1!',
            'Correct horse battery staple 1!',
        );

        $this->assertTrue($form->isValid());
    }

    #[DataProvider('mysqlDb')]
    public function testPasswordChangeIsRejectedWhenPolicyFailsToLoad(DbConnection|string $db): void
    {
        Config::app()->setSection(
            PasswordPolicyHook::CONFIG_SECTION,
            [PasswordPolicyHook::CONFIG_KEY => 'missing/policy'],
        );

        $this->setupDbProvider($db);
        $this->setUpUserTable($db);

        $newPassword = 'correct horse battery staple';
        $form = new ChangePasswordForm(new DbUserBackend($db), new User(static::USER_NAME));
        $form->disableCsrfCounterMeasure();
        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/account'))->withParsedBody([
                'uid'                                                           => 'form_change_password',
                ChangePasswordForm::OLD_PASSWORD_ELEMENT_NAME                   => static::CURRENT_PASSWORD,
                ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME                   => $newPassword,
                ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME . '_confirmation' => $newPassword,
                'submit'                                                        => 'Update Account',
            ]),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertSame(
            'Cannot change the password because the password policy could not be loaded',
            $form->getElement(ChangePasswordForm::NEW_PASSWORD_ELEMENT_NAME)->getMessages()[0]
        );
        $this->assertStringContainsString(
            'There was a problem loading the configured password policy. Please contact your administrator.',
            $form->render(),
        );
        $this->assertTrue(password_verify(
            static::CURRENT_PASSWORD,
            $db->select()
                ->columns(['password_hash'])
                ->from('icingaweb_user')
                ->where('name', static::USER_NAME)
                ->fetchOne(),
        ));
    }

    /**
     * Create a form populated with the given credentials
     *
     * The passwords are typed `mixed` so that tests can submit non-string values,
     * the way a crafted request would.
     *
     * @param DbConnection|string $db
     * @param mixed $oldPassword
     * @param mixed $newPassword
     * @param mixed $newPasswordConfirmation
     *
     * @return ChangePasswordForm
     */
    protected function createForm(
        DbConnection|string $db,
        mixed $oldPassword,
        mixed $newPassword,
        mixed $newPasswordConfirmation,
    ): ChangePasswordForm {
        $this->setupDbProvider($db);
        $this->setUpUserTable($db);

        $form = new ChangePasswordForm(new DbUserBackend($db), new User(static::USER_NAME));
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
