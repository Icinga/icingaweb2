<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Util;

use Icinga\Test\BaseTestCase;
use Icinga\Util\TimezoneDetect;

class TimezoneDetectTest extends BaseTestCase
{
    public function testPositiveTimezoneOffsetSeparatedByComma()
    {
        $this->assertTimezoneDetection('3600,0', 'Europe/Paris');
    }

    public function testPositiveTimezoneOffsetSeparatedByHyphen()
    {
        $this->assertTimezoneDetection('3600-0', 'Europe/Paris');
    }

    public function testNegativeTimezoneOffsetSeparatedByComma()
    {
        $this->assertTimezoneDetection('-3600,0', 'Atlantic/Azores');
    }

    public function testNegativeTimezoneOffsetSeparatedByHyphen()
    {
        $this->assertTimezoneDetection('-3600-0', 'Atlantic/Azores');
    }

    protected function assertTimezoneDetection($cookieValue, $expectedTimezoneName)
    {
        $tzDetect = new TimezoneDetect();
        $tzDetect->reset();

        $_COOKIE[TimezoneDetect::$cookieName] = $cookieValue;
        $tzDetect = new TimezoneDetect();
        $this->assertSame(
            $tzDetect->getTimezoneName(),
            $expectedTimezoneName,
            'Failed asserting that the timezone "' . $expectedTimezoneName
            . '" is being detected from the cookie value "' . $cookieValue . '"'
        );
    }
}
