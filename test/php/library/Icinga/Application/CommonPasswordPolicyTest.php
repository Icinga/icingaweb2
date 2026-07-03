<?php

namespace Tests\Icinga\Application;

use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use PHPUnit\Framework\TestCase;

class CommonPasswordPolicyTest extends TestCase
{
    public function testValidatePasswordTooShort(): void
    {
        $this->assertSame(
            ['Password must be at least 12 characters long'],
            (new CommonPasswordPolicy())->validate('Icinga1#')
        );
    }

    public function testValidatePasswordNoNumber(): void
    {
        $this->assertSame(
            ['Password must contain at least one number'],
            (new CommonPasswordPolicy())->validate('Icingaadmin#')
        );
    }

    public function testValidatePasswordNoSpecialCharacter(): void
    {
        $this->assertSame(
            ['Password must contain at least one special character'],
            (new CommonPasswordPolicy())->validate('Icingaadmin1')
        );
    }

    public function testValidatePasswordNoUpperCaseLetters(): void
    {
        $this->assertSame(
            ['Password must contain at least one uppercase letter'],
            (new CommonPasswordPolicy())->validate('icingaadmin1#')
        );
    }

    public function testValidatePasswordNoLowerCaseLetters(): void
    {
        $this->assertSame(
            ['Password must contain at least one lowercase letter'],
            (new CommonPasswordPolicy())->validate('ICINGAADMIN1#')
        );
    }

    public function testValidatePasswordValid(): void
    {
        $this->assertEmpty((new CommonPasswordPolicy())->validate('Icingaadmin1#'));
    }

    public function testValidatePasswordOnlyLowerCaseLetters(): void
    {
        $expected = [
            'Password must contain at least one number',
            'Password must contain at least one special character',
            'Password must contain at least one uppercase letter'
        ];
        $this->assertSame($expected, (new CommonPasswordPolicy())->validate('icingawebadmin'));
    }

    public function testValidatePasswordToShortAndOnlyUpperCaseLetters(): void
    {
        $expected = [
            'Password must be at least 12 characters long',
            'Password must contain at least one number',
            'Password must contain at least one special character',
            'Password must contain at least one lowercase letter'
        ];
        $this->assertSame($expected, (new CommonPasswordPolicy())->validate('ICINGAADMIN'));
    }
}
