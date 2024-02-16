<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga;

use Icinga\Authentication\Role;
use Mockery;
use DateTimeZone;
use Icinga\User;
use Icinga\Test\BaseTestCase;

class UserTest extends BaseTestCase
{
    public function testGetDefaultTimezoneIfTimezoneNotSet()
    {
        $user = new User('unittest');
        $prefs = Mockery::mock('Icinga\User\Preferences');
        $prefs->shouldReceive('get')->with('timezone')->andReturnNull();
        $user->setPreferences($prefs);

        $this->assertEquals(
            new DateTimeZone(date_default_timezone_get()),
            $user->getTimeZone(),
            'User\'s timezone does not match the default timezone'
        );
    }

    public function testGetTimezoneIfTimezoneSet()
    {
        $explicitTz = 'Europe/Berlin';
        $user = new User('unittest');
        $prefs = Mockery::mock('Icinga\User\Preferences');
        $prefs->shouldReceive('get')->with('timezone')->andReturn($explicitTz);
        $user->setPreferences($prefs);

        $this->assertEquals(
            new DateTimeZone($explicitTz),
            $user->getTimeZone(),
            'User\'s timezone does not match the timezone set by themself'
        );
    }

    public function testWhetherValidEmailsCanBeSet()
    {
        $user = new User('unittest');
        $user->setEmail('mySampleEmail@someDomain.org');

        $this->assertEquals(
            $user->getEmail(),
            'mySampleEmail@someDomain.org',
            'Valid emails set with setEmail are not returned by getEmail'
        );
    }

    public function testWhetherInvalidEmailsCannotBeSet()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new User('unittest');
        $user->setEmail('mySampleEmail at someDomain dot org');
    }

    public function testPermissions()
    {
        $role = new Role();
        $role->setPermissions([
            'test',
            'test/some/specific',
            'test/more/*',
            'test/wildcard-with-wildcard/*',
            'test/even-more/specific-with-wildcard/*'
        ]);

        $user = new User('test');
        $user->setRoles([$role]);

        $this->assertTrue($user->can('test'));
        $this->assertTrue($user->can('test/some/*'));
        $this->assertTrue($user->can('test/some/specific'));
        $this->assertTrue($user->can('test/more/everything'));
        $this->assertTrue($user->can('test/wildcard-with-wildcard/*'));
        $this->assertTrue($user->can('test/wildcard-with-wildcard/sub/sub'));
        $this->assertTrue($user->can('test/even-more/*'));
        $this->assertFalse($user->can('not/test'));
        $this->assertFalse($user->can('test/some/not/so/specific'));
        $this->assertFalse($user->can('test/wildcard2/*'));
    }

    public function testRefusals()
    {
        $role = new Role();
        $role->setPermissions([
            'a',
            'a/b/*',
            'a/b/c/d',
            'c/*',
            'd/*'
        ]);
        $role->setRefusals([
            'a/b/c',
            'a/b/e',
            'c/b/a',
            'c/d/*',
            'd/f',
            'e/g'
        ]);

        $user = new User('test');
        $user->setRoles([$role]);

        $this->assertFalse($user->can('a/b/c'));
        $this->assertFalse($user->can('a/b/e'));
        $this->assertTrue($user->can('a/b/d'));
        $this->assertTrue($user->can('a/b/c/d'));
        $this->assertFalse($user->can('c/b/a'));
        $this->assertTrue($user->can('c/b/d'));
        $this->assertFalse($user->can('c/d/u'));
        $this->assertFalse($user->can('c/d/*'));
        $this->assertTrue($user->can('c/*'));
        $this->assertTrue($user->can('d/*'));
        $this->assertFalse($user->can('e/*'));

        $secondRole = new Role();
        $role->setRefusals(['a/b/*']);

        $user->setRoles([$role, $secondRole]);

        $this->assertFalse($user->can('a/b/d'));
        $this->assertFalse($user->can('a/b/c/d'));
        $this->assertTrue($user->can('c/b/d'));
    }
}
