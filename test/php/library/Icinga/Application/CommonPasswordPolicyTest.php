<?php

namespace Tests\Icinga\Application;

use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use Icinga\Test\BaseTestCase;

class CommonPasswordPolicyTest extends BaseTestCase
{
    private PasswordPolicyHook $instance;

    public function setUp(): void
    {
        $this->instance = new CommonPasswordPolicy();
    }

    public function testMethodGetName(): void
    {
        $this->assertSame('Common', $this->instance->getName());
    }

    public function testValidatePasswordTooShort(): void
    {
        $res = $this->instance->validatePassword('Icinga1#');
        $this->assertSame('Password must be at least 12 characters long', $res[0]);
        $this->assertCount(1, $res);
    }

    public function testValidatePasswordNoNumber(): void
    {
        $res = $this->instance->validatePassword('Icingaadmin#');
        $this->assertSame('Password must contain at least one number', $res[0]);
        $this->assertCount(1, $res);
    }

    public function testValidatePasswordNoSpecialCharacter(): void
    {
        $res = $this->instance->validatePassword('Icingaadmin1');
        $this->assertSame('Password must contain at least one special character', $res[0]);
        $this->assertCount(1, $res);
    }

    public function testValidatePasswordNoUpperCaseLetters(): void
    {
        $res = $this->instance->validatePassword('icingaadmin1#');
        $this->assertSame('Password must contain at least one uppercase letter', $res[0]);
        $this->assertCount(1, $res);
    }

    public function testValidatePasswordNoLowerCaseLetters(): void
    {
        $res = $this->instance->validatePassword('ICINGAADMIN1#');
        $this->assertSame('Password must contain at least one lowercase letter', $res[0]);
        $this->assertCount(1, $res);
    }

    public function testValidatePasswordValid(): void
    {
        $res = $this->instance->validatePassword('Icingaadmin1#');
        $this->assertEmpty($res);
    }

    public function testValidatePasswordOnlyLowerCaseLetters(): void
    {
        $res = $this->instance->validatePassword('icingawebadmin');
        $this->assertCount(3, $res);
        $this->assertSame('Password must contain at least one number', $res[0]);
        $this->assertSame('Password must contain at least one special character', $res[1]);
        $this->assertSame('Password must contain at least one uppercase letter', $res[2]);
    }

    public function testValidatePasswordWithLengthAndUpperCaseLetters(): void
    {
        $res = $this->instance->validatePassword('ICINGAADMIN');
        $this->assertCount(4, $res);
        $this->assertSame('Password must be at least 12 characters long', $res[0]);
        $this->assertSame('Password must contain at least one number', $res[1]);
        $this->assertSame('Password must contain at least one special character', $res[2]);
        $this->assertSame('Password must contain at least one lowercase letter', $res[3]);
    }

    public function testValidatePasswordWithManyCharacters(): void
    {
        $longPassword = str_repeat('a', 1000);
        $res = $this->instance->validatePassword($longPassword);
        $this->assertCount(3, $res);
    }
 }



