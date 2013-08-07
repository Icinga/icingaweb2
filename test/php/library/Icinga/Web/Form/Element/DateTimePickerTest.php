<?php

namespace Test\Icinga\Web\Form\Element;

require_once 'Zend/Form/Element/Xhtml.php';
require_once __DIR__ . '/../../../../../../../library/Icinga/Application/Icinga.php';
require_once __DIR__ . '/../../../../../../../library/Icinga/Web/Form/Element/DateTimePicker.php';

use \DateTimeZone;
use Icinga\Web\Form\Element\DateTimePicker;

class DateTimeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // Set default timezone else new DateTime calls will die with the Exception that it's
        // not safe to rely on the system's timezone
        date_default_timezone_set('UTC');
    }

    public function testValidateInvalidInput()
    {
        $dt = new DateTimePicker('foo');
        // Set element's timezone else it'll try to load the time zone from the user
        // which requires Icinga::app() to be bootstrapped which is not the case
        // within tests - so without a ProgrammingError will be thrown
        $dt->setTimeZone(new DateTimeZone('UTC'));

        $this->assertFalse(
            $dt->isValid('bar'),
            'Arbitrary strings must not be valid input'
        );
        $this->assertFalse(
            $dt->isValid('13736a16223'),
            'Invalid Unix timestamps must not be valid input'
        );
    }

    public function testValidateValidInput()
    {
        $dt = new DateTimePicker('foo');
        $dt->setTimeZone(new DateTimeZone('UTC'));

        $this->assertTrue(
            $dt->isValid('2013-07-12 08:03:43'),
            'Using a valid date/time string must not fail'
        );
        $this->assertTrue(
            $dt->isValid('@' . 1373616223),
            'Using the Unix timestamp format must not fail'
        );
        $this->assertTrue(
            $dt->isValid(1373616223),
            'Using valid Unix timestamps must not fail'
        );
    }

    public function testGetValueReturnsUnixTimestamp()
    {
        $dt = new DateTimePicker('foo');
        $dt->setTimeZone(new DateTimeZone('UTC'))
            ->setValue('2013-07-12 08:03:43');

        $this->assertEquals(
            $dt->getValue(),
            1373616223,
            'getValue did not return the correct Unix timestamp according to the given date/time '
                . 'string'
        );
    }

    public function testGetValueIsTimeZoneAware()
    {
        $dt = new DateTimePicker('foo');
        $dt->setTimeZone(new DateTimeZone('Europe/Berlin'))
            ->setValue('2013-07-12 08:03:43');

        $this->assertEquals(
            $dt->getValue(),
            1373609023,
            'getValue did not return the correct Unix timestamp according to the given date/time '
                . 'string'
        );
    }
}
