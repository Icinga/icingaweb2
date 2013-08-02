<?php

namespace Test\Monitoring\Forms\Command;

require_once __DIR__. '/BaseFormTest.php';
require_once __DIR__. '/../../../../../application/forms/Command/ConfirmationForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/DelayNotificationForm.php';


use \Zend_View;
use \Zend_Test_PHPUnit_ControllerTestCase;
use Monitoring\Form\Command\DelayNotificationForm;

class DelayNotificationFormFormTest extends BaseFormTest
{
    public function testValidForm()
    {
        $form = $this->getRequestForm(array(
            'minutes' => 12
        ), 'Monitoring\Form\Command\DelayNotificationForm');

        $form->buildForm();
        $this->assertCount(5, $form->getElements());

        $element = $form->getElement('minutes');
        $this->assertInstanceOf('Zend_Form_Element_Text', $element);
        $this->assertEquals('0', $element->getValue(), "Assert a correct default value in minutes");
        $this->assertTrue($element->isRequired(), "Assert minutes to be declared as required");

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            "Assert a correct DelayNotificationForm to be considered valid"
        );

        $this->assertEquals('12', $form->getValue('minutes'), "Assert the minutes field to be correctly populated");
    }

    public function testInvalidMinuteValue()
    {
        $form = $this->getRequestForm(array(
            'minutes' => 'SCHAHH-LAHH-LAHH'
        ), 'Monitoring\Form\Command\DelayNotificationForm');

        $form->buildForm();

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            "Asserting invalid minutes (NaN) to cause validation errors"
        );

        $errors = $form->getErrors('minutes');
        $this->assertEquals('notBetween', $errors[0], "Assert correct error message");
    }
}

