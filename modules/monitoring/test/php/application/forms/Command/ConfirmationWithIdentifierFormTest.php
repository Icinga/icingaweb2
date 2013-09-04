<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Test/BaseTestCase.php');

use Icinga\Test\BaseTestCase;

require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/CommandForm.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/WithChildrenCommandForm.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/CommandWithIdentifierForm.php';

class CommandWithIdentifierFormTest extends BaseTestCase
{
    const FORM_CLASS = '\Icinga\Module\Monitoring\Form\Command\CommandWithIdentifierForm';

    public function testFormInvalidWhenObjectIdMissing()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'object_id'     => '',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing object_id must be considered not valid'
        );
    }

    public function testFormInvalidWhenObjectIdNonDigit()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'object_id'     => 'A Service',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Non numeric input must be considered not valid'
        );
    }

    public function testFormValidWhenObjectIdIsDigit()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'object_id'     => 1,
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Digits must be considered valid'
        );
    }
}
