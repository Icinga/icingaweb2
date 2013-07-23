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
    require_once __DIR__. '/../../../../../application/forms/Command/CustomNotificationForm.php';


    use Monitoring\Form\Command\CustomNotificationForm;
    use \Zend_View;
    use \Zend_Test_PHPUnit_ControllerTestCase;

    class CustomNotificationFormTest extends Zend_Test_PHPUnit_ControllerTestCase
    {
        public function testForm1()
        {
            $this->getRequest()->setMethod('POST');
            $this->getRequest()->setPost(
                array(
                    'comment' => 'TEST COMMENT',
                    'author'  => 'LAOLA'
                )
            );

            $form = new CustomNotificationForm();
            $form->setRequest($this->getRequest());
            $form->buildForm();

            $this->assertCount(7, $form->getElements());
            $this->assertTrue($form->isPostAndValid());
        }
    }
}
