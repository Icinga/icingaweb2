<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web\Form\Validator;

use Icinga\Test\BaseTestCase;
use Icinga\Web\Form\Validator\DateFormatValidator;

class DateFormatValidatorTest extends BaseTestCase
{
    public function testValidateCorrectInput()
    {
        $validator = new DateFormatValidator();
        $this->assertTrue(
            $validator->isValid(
                'Y-m-d',
                'Asserting a valid date format to result in correct validation'
            )
        );
    }

    public function testValidateInorrectInput()
    {
        $validator = new DateFormatValidator();
        $this->assertFalse(
            $validator->isValid(
                'Y-m-d h:m:s',
                'Asserting a date format combined with time to result in a validation error'
            )
        );
    }
}
