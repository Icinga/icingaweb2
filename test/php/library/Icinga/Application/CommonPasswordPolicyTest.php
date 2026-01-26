<?php

namespace Tests\Icinga\Application;

use Icinga\Authentication\PasswordPolicy;
use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use PHPUnit\Framework\TestCase;

class CommonPasswordPolicyTest extends TestCase
{
    protected PasswordPolicy $instance;

    public function setUp(): void
    {
        $this->instance = new CommonPasswordPolicy();
    }

    public function testValidatePasswordTooShort(): void
    {
        $this->assertSame(
            ['Password must be at least 12 characters long'],
            $this->instance->validate('Icinga1#', 'null')
        );
    }

    public function testValidatePasswordNoNumber(): void
    {
        $this->assertSame(
            ['Password must contain at least one number'],
            $this->instance->validate('Icingaadmin#', 'null')
        );
    }

    public function testValidatePasswordNoSpecialCharacter(): void
    {
        $this->assertSame(
            ['Password must contain at least one special character'],
            $this->instance->validate('Icingaadmin1', 'null')
        );
    }

    public function testValidatePasswordNoUpperCaseLetters(): void
    {
        $this->assertSame(
            ['Password must contain at least one uppercase letter'],
            $this->instance->validate('icingaadmin1#', 'null')
        );
    }

    public function testValidatePasswordNoLowerCaseLetters(): void
    {
        $this->assertSame(
            ['Password must contain at least one lowercase letter'],
            $this->instance->validate('ICINGAADMIN1#', 'null')
        );
    }

    public function testMethodValidatePasswordAlwaysReturnAnEmptyArray(): void
    {
        $this->assertEmpty($this->instance->validate('Icingaadmin1#', 'null'));
    }

    public function testValidatePasswordOnlyLowerCaseLetters(): void
    {
        $expected = [
            'Password must contain at least one number',
            'Password must contain at least one special character',
            'Password must contain at least one uppercase letter'
        ];
        $this->assertSame(
            $expected,
            $this->instance->validate('icingawebadmin', 'null')
        );
    }

    public function testValidatePasswordWithLengthAndUpperCaseLetters(): void
    {
        $expectedResult = [
            'Password must be at least 12 characters long',
            'Password must contain at least one number',
            'Password must contain at least one special character',
            'Password must contain at least one lowercase letter',
        ];
        $res =
        $this->assertSame(
            $expectedResult,
            $this->instance->validate('ICINGAADMIN', 'null')
        );
    }

    public function testValidatePasswordWithManyCharacters(): void
    {
        $longPassword = str_repeat('a', 1000);
        $this->assertCount(3, $this->instance->validate($longPassword, 'null'));
    }
 }
