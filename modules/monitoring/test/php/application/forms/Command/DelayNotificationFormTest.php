<?php

namespace {
    if (!function_exists('t')) {
        function t() {
            return func_get_arg(0);
        }
    }

    if (!function_exists('mt')) {
        function mt() {
            return func_get_arg(0);
        }
    }
}

namespace Test\Monitoring\Forms\Command {

    require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';
    require_once 'Zend/Form.php';
    require_once 'Zend/View.php';
    require_once 'Zend/Form/Element/Submit.php';
    require_once 'Zend/Form/Element/Reset.php';

    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form.php';
    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form/Element/Note.php';
    require_once __DIR__. '/../../../../../application/forms/Command/ConfirmationForm.php';
    require_once __DIR__. '/../../../../../application/forms/Command/DelayNotificationForm.php';


    use \Zend_View;
    use \Zend_Test_PHPUnit_ControllerTestCase;
    use Monitoring\Form\Command\DelayNotificationForm;

    class DelayNotificationFormFormTest extends Zend_Test_PHPUnit_ControllerTestCase
    {
        public function testForm1()
        {
            $this->getRequest()->setMethod('POST');
            $this->getRequest()->setPost(
                array(
                    'minutes' => 12
                )
            );

            $form = new DelayNotificationForm();

            $form->setRequest($this->getRequest());
            $form->buildForm();

            $this->assertCount(5, $form->getElements());

            $element = $form->getElement('minutes');
            $this->assertInstanceOf('Zend_Form_Element_Text', $element);
            $this->assertEquals('0', $element->getValue());
            $this->assertTrue($element->isRequired());

            $this->assertTrue($form->isPostAndValid());

            $this->assertEquals('12', $form->getValue('minutes'));
        }

        public function testValidation()
        {
            $this->getRequest()->setMethod('POST');
            $this->getRequest()->setPost(
                array(
                    'minutes' => 'SCHAHH-LAHH-LAHH'
                )
            );

            $form = new DelayNotificationForm();

            $form->setRequest($this->getRequest());
            $form->buildForm();

            $this->assertFalse($form->isPostAndValid());

            $errors = $form->getErrors('minutes');
            $this->assertEquals('notBetween', $errors[0]);
        }
    }

}
