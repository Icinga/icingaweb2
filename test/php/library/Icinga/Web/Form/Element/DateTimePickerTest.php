<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web\Form\Element;

use DateTime;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Form\Element\DateTimePicker;

class DateTimePickerTest extends BaseTestCase
{
    public function testLocalDateAndTimeInput()
    {
        $dateTime = new DateTimePicker(
            'name'
        );
        $now = new DateTime();
        $this->assertTrue(
            $dateTime->isValid($now->format('Y-m-d\TH:i:s')),
            'A string representing a local date and time (with no timezone information) must be considered valid input'
        );
        $this->assertTrue(
            $dateTime->getValue() instanceof DateTime,
            'DateTimePicker::getValue() must return an instance of DateTime if its input is valid'
        );
    }

    public function testRFC3339Input()
    {
        $dateTime = new DateTimePicker(
            'name',
            array(
                'local' => false
            )
        );
        $now = new DateTime();
        $this->assertTrue(
            $dateTime->isValid($now->format(DateTime::RFC3339)),
            'A string representing a global date and time (with timezone information) must be considered valid input'
        );
        $this->assertTrue(
            $dateTime->getValue() instanceof DateTime,
            'DateTimePicker::getValue() must return an instance of DateTime if its input is valid'
        );
    }
}
