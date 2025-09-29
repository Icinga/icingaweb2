<?php

namespace Tests\Icinga\Application;

use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use PHPUnit\Framework\TestCase;

class CommonPasswordPolicyTest extends TestCase
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
        $expectedResults = ['Password must be at least 12 characters long'];
        $res = $this->instance->validatePassword('Icinga1#');
        $this->assertSame($expectedResults, $res);
    }

    public function testValidatePasswordNoNumber(): void
    {
        $expectedResults = ['Password must contain at least one number'];
        $res = $this->instance->validatePassword('Icingaadmin#');
        $this->assertSame($expectedResults, $res);
    }

    public function testValidatePasswordNoSpecialCharacter(): void
    {
        $expectedResult = ['Password must contain at least one special character'];
        $res = $this->instance->validatePassword('Icingaadmin1');
        $this->assertSame($expectedResult, $res);
    }

    public function testValidatePasswordNoUpperCaseLetters(): void
    {
       $expectedResult = ['Password must contain at least one uppercase letter'];
        $res = $this->instance->validatePassword('icingaadmin1#');
        $this->assertSame($expectedResult, $res);
    }

    public function testValidatePasswordNoLowerCaseLetters(): void
    {
        $expectedResult = ['Password must contain at least one lowercase letter'];
        $res = $this->instance->validatePassword('ICINGAADMIN1#');
        $this->assertSame($expectedResult, $res);
    }

    public function testMethodValidatePasswordAlwaysReturnAnEmptyArray(): void
    {
        $res = $this->instance->validatePassword('Icingaadmin1#');
        $this->assertEmpty($res);
    }

    public function testValidatePasswordOnlyLowerCaseLetters(): void
    {
        $expectedResult = [
            'Password must contain at least one number',
            'Password must contain at least one special character',
            'Password must contain at least one uppercase letter'
        ];
        $res = $this->instance->validatePassword('icingawebadmin');
        $this->assertSame($expectedResult, $res);
    }

    public function testValidatePasswordWithLengthAndUpperCaseLetters(): void
    {
        $expectedResult = [
            'Password must be at least 12 characters long',
            'Password must contain at least one number',
            'Password must contain at least one special character',
            'Password must contain at least one lowercase letter',
        ];
        $res = $this->instance->validatePassword('ICINGAADMIN');
        $this->assertSame($expectedResult, $res);
    }

    public function testValidatePasswordWithManyCharacters(): void
    {
        $longPassword = str_repeat('a', 1000);
        $res = $this->instance->validatePassword($longPassword);
        $this->assertCount(3, $res);
    }
 }
