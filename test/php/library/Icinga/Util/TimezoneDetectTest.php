<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Util;

use Icinga\Test\BaseTestCase;
use Icinga\Util\TimezoneDetect;

class TimezoneDetectTest extends BaseTestCase
{
    public function testInvalidTimezoneNameInCookie(): void
    {
        $tzDetect = new TimezoneDetect();
        $tzDetect->reset();
        
        $_COOKIE[TimezoneDetect::$cookieName] = 'ABC';
        $tzDetect = new TimezoneDetect();
        $this->assertFalse(
            $tzDetect->success(),
            false,
            'Failed to assert invalid timezone name is detected'
        );

        $this->assertNull(
            $tzDetect->getTimezoneName(),
            'Failed to assert that the timezone name will not be set for invalid timezone'
        );
    }

    public function testValidTimezoneNameInCookie(): void
    {
        $tzDetect = new TimezoneDetect();
        $tzDetect->reset();

        $_COOKIE[TimezoneDetect::$cookieName] = "Europe/Berlin";
        $tzDetect = new TimezoneDetect();
        $this->assertTrue(
            $tzDetect->success(),
            true,
            'Failed to assert that the valid timezone name is detected'
        );

        $this->assertSame(
            $tzDetect->getTimezoneName(),
            "Europe/Berlin",
            'Failed to assert that the valid timezone name was correctly set'
        );
    }
}
