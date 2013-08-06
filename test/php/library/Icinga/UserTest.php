<?php

namespace Tests\Icinga;

require_once __DIR__ . '/../../../../library/Icinga/User.php';
require_once __DIR__ . '/../../../../library/Icinga/User/Preferences.php';
require_once __DIR__ . '/../../../../library/Icinga/User/Preferences/ChangeSet.php';

use Icinga\User as IcingaUser;
use Icinga\User\Preferences as UserPreferences;

class UserTest extends \PHPUnit_Framework_TestCase
{

    public function testListGroups()
    {
        $this->markTestIncomplete('testListGroups is not implemented yet');
    }

    public function testIsMemberOf()
    {
        $this->markTestIncomplete('testIsMemberOf is not implemented yet');
    }

    public function testGetPermissionList()
    {
        $this->markTestIncomplete('testGetPermissionList is not implemented yet');
    }

    public function testHasPermission()
    {
        $this->markTestIncomplete('testHasPermission is not implemented yet');
    }

    public function testGrantPermission()
    {
        $this->markTestIncomplete('testGrantPermission is not implemented yet');
    }

    public function testRevokePermission()
    {
        $this->markTestIncomplete('testRevokePermission is not implemented yet');
    }

    public function testGetDefaultTimezoneIfTimezoneNotSet()
    {
        $defaultTz = 'UTC';
        date_default_timezone_set($defaultTz);
        $user = new IcingaUser('unittest');
        $prefs = new UserPreferences(array());
        $user->setPreferences($prefs);
        $this->assertEquals($user->getTimeZone(), $defaultTz,
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
        $this->assertEquals($user->getTimeZone(), $explicitTz,
            'User\'s timezone does not match the timezone set by himself'
        );
    }

}
