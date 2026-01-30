<?php

namespace Tests\Icinga\Application;

use Icinga\Authentication\PasswordPolicy;
use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use PHPUnit\Framework\TestCase;

class CommonPasswordPolicyTest extends TestCase
{
    protected passwordPolicy $instance;

    public function setUp(): void
    {
        $this->instance = new CommonPasswordPolicy();
    }

    public function testValidatePasswordTooShort(): void
    {
        $this->assertSame(
            ['Password must be at least 12 characters long'],
            $this->instance->validate('Icinga1#')
        );
    }

    public function testValidatePasswordNoNumber(): void
    {
        $this->assertSame(
            ['Password must contain at least one number'],
            $this->instance->validate('Icingaadmin#')
        );
    }

    public function testValidatePasswordNoSpecialCharacter(): void
    {
        $this->assertSame(
            ['Password must contain at least one special character'],
            $this->instance->validate('Icingaadmin1')
        );
    }

    public function testValidatePasswordNoUpperCaseLetters(): void
    {
        $this->assertSame(
            ['Password must contain at least one uppercase letter'],
            $this->instance->validate('icingaadmin1#')
        );
    }

    public function testValidatePasswordNoLowerCaseLetters(): void
    {
        $this->assertSame(
            ['Password must contain at least one lowercase letter'],
            $this->instance->validate('ICINGAADMIN1#')
        );
    }

    public function testValidatePasswordValid(): void
    {
        $this->assertEmpty($this->instance->validate('Icingaadmin1#'));
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
            $this->instance->validate('icingawebadmin')
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
        $this->assertSame(
            $expectedResult,
            $this->instance->validate('ICINGAADMIN')
        );
    }
 }
