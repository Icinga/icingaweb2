<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga;

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
            'User\'s timezone does not match the timezone set by himself'
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

    /**
     * @expectedException   \InvalidArgumentException
     */
    public function testWhetherInvalidEmailsCannotBeSet()
    {
        $user = new User('unittest');
        $user->setEmail('mySampleEmail at someDomain dot org');
    }

    public function testPermissions()
    {
        $user = new User('test');
        $user->setPermissions(array(
            'test',
            'test/some/specific',
            'test/more/*'
        ));
        $this->assertTrue($user->can('test'));
        $this->assertTrue($user->can('test/some/specific'));
        $this->assertTrue($user->can('test/more/everything'));
        $this->assertFalse($user->can('not/test'));
        $this->assertFalse($user->can('test/some/not/so/specific'));
    }
}
