<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web\Form\Validator;

use Icinga\Test\BaseTestCase;
use Icinga\Web\Form\Validator\WritablePathValidator;

class WritablePathValidatorTest extends BaseTestCase
{
    public function testValidateInputWithWritablePath()
    {
        $validator = new WritablePathValidator();
        if (!is_writeable('/tmp')) {
            $this->markTestSkipped('Need /tmp to be writable for testing WritablePathValidator');
        }
        $this->assertTrue(
            $validator->isValid(
                '/tmp/test',
                'Asserting a writable path to result in correct validation'
            )
        );
    }

    public function testValidateInputWithNonWritablePath()
    {
        $validator = new WritablePathValidator();
        $this->assertFalse(
            $validator->isValid(
                '/etc/shadow',
                'Asserting a non writable path to result in a validation error'
            )
        );
    }
}
