<?php

namespace Tests\Icinga\Application;

use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use Icinga\User;
use PHPUnit\Framework\TestCase;

class CommonPasswordPolicyTest extends TestCase
{
    public function testValidatePasswordTooShort(): void
    {
        $this->assertSame(
            ['Password must be at least 12 characters long'],
            (new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'Test1#')
        );
    }

    public function testValidatePasswordNoNumber(): void
    {
        $this->assertSame(
            ['Password must contain at least one number'],
            (new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'TestPassword#')
        );
    }

    public function testValidatePasswordNoSpecialCharacter(): void
    {
        $this->assertSame(
            ['Password must contain at least one special character'],
            (new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'TestPassword1')
        );
    }

    public function testValidatePasswordNoUpperCaseLetters(): void
    {
        $this->assertSame(
            ['Password must contain at least one uppercase letter'],
            (new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'testpassword1#')
        );
    }

    public function testValidatePasswordNoLowerCaseLetters(): void
    {
        $this->assertSame(
            ['Password must contain at least one lowercase letter'],
            (new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'TESTPASSWORD1#')
        );
    }

    public function testValidatePasswordValid(): void
    {
        $this->assertEmpty((new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'Testpassword1#'));
    }

    public function testValidatePasswordOnlyLowerCaseLetters(): void
    {
        $expected = [
            'Password must contain at least one number',
            'Password must contain at least one special character',
            'Password must contain at least one uppercase letter'
        ];
        $this->assertSame($expected, (new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'testpassword'));
    }

    public function testValidatePasswordToShortAndOnlyUpperCaseLetters(): void
    {
        $expected = [
            'Password must be at least 12 characters long',
            'Password must contain at least one number',
            'Password must contain at least one special character',
            'Password must contain at least one lowercase letter'
        ];
        $this->assertSame($expected, (new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'TEST'));
    }

    public function testValidatePasswordEqualToUsernameIsRejected(): void
    {
        $this->assertContains(
            'Username and password must not match',
            (new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'icingaadmin')
        );
    }

    public function testValidatePasswordContainedInUsernameIsRejected(): void
    {
        $this->assertContains(
            'Password must not be contained in username',
            (new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'icinga')
        );
    }

    public function testValidatePasswordContainingUsernameIsRejected(): void
    {
        $this->assertContains(
            'Password must not contain username',
            (new CommonPasswordPolicy())->validate(new User('icingaadmin'), 'Icingaadmin1!')
        );
    }

    public function testValidateUsernameComparisonIsCaseInsensitive(): void
    {
        $this->assertContains(
            'Username and password must not match',
            (new CommonPasswordPolicy())->validate(new User('IcingaAdmin'), 'icingaadmin')
        );
    }
}
