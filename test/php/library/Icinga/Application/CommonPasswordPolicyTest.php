<?php

namespace Tests\Icinga\Application;

use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use Icinga\Test\BaseTestCase;

class CommonPasswordPolicyTest extends BaseTestCase
{
    private object $object;

    public function setUp(): void
    {
        $this->object = new CommonPasswordPolicy();
    }

    public function testClassIsInstanzOf()
    {
        $this->assertInstanceOf(PasswordPolicyHook::class, $this->object);
    }

    public function testMethodGetName(): void
    {
        $this->assertSame('Common', $this->object->getName());
    }

    public function testValidatePasswordTooShort()
    {
        $res = $this->object->validatePassword('Icinga1#');
        $this->assertSame('Password must be at least 12 characters long', $res[0]);
        $this->assertCount(1, $res);
    }

    public function testValidatePasswordNoNumber()
    {
        $res = $this->object->validatePassword('Icingaadmin#');
        $this->assertSame('Password must contain at least one number', $res[0]);
        var_dump($res);
        $this->assertCount(1, $res);
    }

    public function testValidatePasswordNoSpecialCharacter()
    {
        $res = $this->object->validatePassword('Icingaadmin1');
        $this->assertSame('Password must contain at least one special character', $res[0]);
        $this->assertCount(1, $res);
    }

    public function testValidatePasswordNoUpperCaseLetters()
    {
        $res = $this->object->validatePassword('icingaadmin1#');
        $this->assertSame('Password must contain at least one uppercase letter', $res[0]);
        $this->assertCount(1, $res);
    }

    public function testValidatePasswordNoLowerCaseLetters()
    {
        $res = $this->object->validatePassword('ICINGAADMIN1#');
        $this->assertSame('Password must contain at least one lowercase letter', $res[0]);
        $this->assertCount(1, $res);
    }

    public function testValidatePasswordValid()
    {
        $res = $this->object->validatePassword('Icingaadmin1#');
        $this->assertEmpty($res);
    }

    public function testValidatePasswordOnlyLowerCaseLetters()
    {
        $res = $this->object->validatePassword('icingawebadmin');
        var_dump($res);
        $this->assertCount(3, $res);
        $this->assertSame('Password must contain at least one number', $res[0]);
        $this->assertSame('Password must contain at least one special character', $res[1]);
        $this->assertSame('Password must contain at least one uppercase letter', $res[2]);
    }

    public function testValidatePasswordWithLengthAndUpperCaseLetters()
    {
        $res = $this->object->validatePassword('ICINGAADMIN');
        var_dump($res);
        $this->assertCount(4, $res);
        $this->assertSame('Password must be at least 12 characters long', $res[0]);
        $this->assertSame('Password must contain at least one number', $res[1]);
        $this->assertSame('Password must contain at least one special character', $res[2]);
        $this->assertSame('Password must contain at least one lowercase letter', $res[3]);
    }

    public function testValidatePasswordWithManyCharacters()
    {
        $longPassword = str_repeat('a', 1000);
        var_dump($longPassword);
        $res = $this->object->validatePassword($longPassword);
        var_dump($res);
        $this->assertCount(3, $res);
    }
 }



