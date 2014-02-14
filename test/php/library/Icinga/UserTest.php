<?php

namespace Tests\Icinga;

use \DateTimeZone;
use Icinga\User as IcingaUser;
use Icinga\User\Preferences as UserPreferences;

class UserTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDefaultTimezoneIfTimezoneNotSet()
    {
        $defaultTz = 'UTC';
        date_default_timezone_set($defaultTz);
        $user = new IcingaUser('unittest');
        $prefs = new UserPreferences(array());
        $user->setPreferences($prefs);
        $this->assertEquals($user->getTimeZone(), new DateTimeZone($defaultTz),
            'User\'s timezone does not match the default timezone'
        );
    }

    public function testGetTimezoneIfTimezoneSet()
    {
        $defaultTz = 'UTC';
        $explicitTz = 'Europe/Berlin';
        date_default_timezone_set($defaultTz);
        $user = new IcingaUser('unittest');
        $prefs = new UserPreferences(array(
            'timezone' => $explicitTz
        ));
        $user->setPreferences($prefs);

        $this->assertEquals($user->getTimeZone(), new DateTimeZone($explicitTz),
            'User\'s timezone does not match the timezone set by himself'
        );
    }

}
