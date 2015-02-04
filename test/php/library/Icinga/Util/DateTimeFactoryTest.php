<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Util;

use DateTimeZone;
use Icinga\Test\BaseTestCase;
use Icinga\Util\DateTimeFactory;

class DateTimeFactoryTest extends BaseTestCase
{
    /**
     * @expectedException Icinga\Exception\ConfigurationError
     */
    public function testWhetherSetConfigThrowsAnExceptionWhenTimezoneMissing()
    {
        DateTimeFactory::setConfig(array());
    }

    /**
     * @expectedException Icinga\Exception\ConfigurationError
     */
    public function testWhetherSetConfigThrowsAnExceptionWhenTimezoneInvalid()
    {
        DateTimeFactory::setConfig(array('timezone' => 'invalid'));
    }

    public function testWhetherParseWorksWithASpecificTimezone()
    {
        $dt = DateTimeFactory::parse('17-04-14 17:00', 'd-m-y H:i', new DateTimeZone('Europe/Berlin'));
        $dt->setTimezone(new DateTimeZone('UTC'));

        $this->assertEquals(
            '15',
            $dt->format('H'),
            'DateTimeFactory::parse does not properly parse a given datetime or does not respect the given timezone'
        );
    }

    public function testWhetherParseWorksWithoutASpecificTimezone()
    {
        $this->assertEquals(
            '15',
            DateTimeFactory::parse('17-04-14 15:00', 'd-m-y H:i')->format('H'),
            'DateTimeFactory::parse does not properly parse a given datetime'
        );
    }

    public function testWhetherCreateWorksWithASpecificTimezone()
    {
        $dt = DateTimeFactory::create('2014-04-17 5PM', new DateTimeZone('Europe/Berlin'));
        $dt->setTimezone(new DateTimeZone('UTC'));

        $this->assertEquals(
            '15',
            $dt->format('H'),
            'DateTimeFactory::create does not properly parse a given datetime or does not respect the given timezone'
        );
    }

    public function testWhetherCreateWorksWithoutASpecificTimezone()
    {
        $this->assertEquals(
            '15',
            DateTimeFactory::create('2014-04-17 3PM')->format('H'),
            'DateTimeFactory::create does not properly parse a given datetime'
        );
    }
}
