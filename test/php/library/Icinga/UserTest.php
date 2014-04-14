<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga;

use \DateTimeZone;
use Icinga\User;
use Icinga\User\Preferences;
use Icinga\Test\BaseTestCase;

class UserTest extends BaseTestCase
{
    public function testGetDefaultTimezoneIfTimezoneNotSet()
    {
        $user = new User('unittest');
        $prefs = new Preferences(array());
        $user->setPreferences($prefs);
        $this->assertEquals($user->getTimeZone(), new DateTimeZone(date_default_timezone_get()),
            'User\'s timezone does not match the default timezone'
        );
    }

    public function testGetTimezoneIfTimezoneSet()
    {
        $explicitTz = 'Europe/Berlin';
        $user = new User('unittest');
        $prefs = new Preferences(array(
            'timezone' => $explicitTz
        ));
        $user->setPreferences($prefs);

        $this->assertEquals($user->getTimeZone(), new DateTimeZone($explicitTz),
            'User\'s timezone does not match the timezone set by himself'
        );
    }

}
