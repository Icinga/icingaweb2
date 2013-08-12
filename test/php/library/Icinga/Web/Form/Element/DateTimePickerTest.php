<?php

namespace Test\Icinga\Web\Form\Element;

require_once 'Zend/Form/Element/Xhtml.php';
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Application/Icinga.php');
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Web/Form/Element/DateTimePicker.php');
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Util/ConfigAwareFactory.php');
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Util/DateTimeFactory.php');

use \DateTimeZone;
use \PHPUnit_Framework_TestCase;
use \Icinga\Web\Form\Element\DateTimePicker;
use \Icinga\Util\DateTimeFactory;

class DateTimeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // Set default timezone else new DateTime calls will die with the Exception that it's
        // not safe to rely on the system's timezone
        date_default_timezone_set('UTC');
    }

    /**
     * Test that DateTimePicker::isValid() returns false if the input is not valid in terms of being a date/time string
     * or a timestamp
     *
     * Utilizes singleton DateTimeFactory
     *
     * @backupStaticAttributes enabled
     */
    public function testValidateInvalidInput()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));

        $dt = new DateTimePicker('foo');

        $this->assertFalse(
            $dt->isValid('bar'),
            'Arbitrary strings must not be valid input'
        );
        $this->assertFalse(
            $dt->isValid('13736a16223'),
            'Invalid Unix timestamps must not be valid input'
        );
    }

    /**
     * Test that DateTimePicker::isValid() returns true if the input is valid in terms of being a date/time string
     * or a timestamp
     *
     * Utilizes singleton DateTimeFactory
     *
     * @backupStaticAttributes enabled
     */
    public function testValidateValidInput()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));

        $dt = new DateTimePicker('foo');

        $this->assertTrue(
            $dt->isValid('2013-07-12 08:03:43'),
            'Using a valid date/time string must not fail'
        );
        $this->assertTrue(
            $dt->isValid(1373616223),
            'Using valid Unix timestamps must not fail'
        );
    }

    /**
     * Test that DateTimePicker::getValue() returns a timestamp after a call to isValid considers the input as correct
     *
     * Utilizes singleton DateTimeFactory
     *
     * @backupStaticAttributes enabled
     */
    public function testGetValueReturnsUnixTimestampAfterSuccessfulIsValidCall()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));

        $dt = new DateTimePicker('foo');
        $dt->setValue('2013-07-12 08:03:43');

        $this->assertTrue(
            $dt->isValid($dt->getValue()),
            'Using a valid date/time string must not fail'
        );

        $this->assertEquals(
            $dt->getValue(),
            1373616223,
            'getValue did not return the correct Unix timestamp according to the given date/time '
                . 'string'
        );
    }

    /**
     * Test that DateTimePicker::getValue() returns a timestamp respecting the given non-UTC time zone after a call to
     * isValid considers the input as correct
     *
     * Utilizes singleton DateTimeFactory
     *
     * @backupStaticAttributes enabled
     */
    public function testGetValueIsTimeZoneAware()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('Europe/Berlin')));

        $dt = new DateTimePicker('foo');
        $dt->setValue('2013-07-12 08:03:43');

        $this->assertTrue(
            $dt->isValid($dt->getValue()),
            'Using a valid date/time string must not fail'
        );

        $this->assertEquals(
            $dt->getValue(),
            1373609023,
            'getValue did not return the correct Unix timestamp according to the given date/time string and time zone'
        );
    }
}
