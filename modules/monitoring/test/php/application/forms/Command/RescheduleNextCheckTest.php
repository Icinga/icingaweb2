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
    require_once 'Zend/Form/Element/Checkbox.php';
    require_once 'Zend/Validate/Date.php';

    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form.php';
    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form/Element/Note.php';
    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form/Element/DateTime.php';
    require_once __DIR__. '/../../../../../application/forms/Command/AbstractCommand.php';
    require_once __DIR__. '/../../../../../application/forms/Command/WithChildrenCommand.php';
    require_once __DIR__. '/../../../../../application/forms/Command/RescheduleNextCheck.php';


    use Monitoring\Form\Command\RescheduleNextCheck;
    use \Zend_View;
    use \Zend_Test_PHPUnit_ControllerTestCase;

    class RescheduleNextCheckTest extends Zend_Test_PHPUnit_ControllerTestCase
    {
        public function testForm1()
        {
            $this->getRequest()->setPost(
                array(

                )
            );

            $form = new RescheduleNextCheck();
            $form->setRequest($this->getRequest());
            $form->buildForm();

            $this->assertCount(6, $form->getElements());

            $this->assertTrue(
                $form->isValid(
                    array(
                        'checktime'  => '2013-10-19 17:30:00',
                        'forcecheck' => 1
                    )
                )
            );

            $this->assertTrue(
                $form->isValid(
                    array(
                        'checktime'  => '2013-10-19 17:30:00',
                        'forcecheck' => 0
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'checktime'  => '2013-24-12 17:30:00',
                        'forcecheck' => 1
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'checktime'  => 'AHAHA',
                        'forcecheck' => 1
                    )
                )
            );
        }

        public function testChildrenFlag()
        {

            $form = new RescheduleNextCheck();
            $form->setRequest($this->getRequest());
            $form->setWithChildren(true);
            $form->buildForm();
            $notes1 = $form->getNotes();
            $form = null;

            $form = new RescheduleNextCheck();
            $form->setRequest($this->getRequest());
            $form->setWithChildren(false);
            $form->buildForm();
            $notes2 = $form->getNotes();
            $form = null;

            $form = new RescheduleNextCheck();
            $form->setRequest($this->getRequest());
            $form->setWithChildren();
            $form->buildForm();
            $notes3 = $form->getNotes();
            $form = null;

            $this->assertEquals($notes1, $notes3);
            $this->assertNotEquals($notes1, $notes2);
        }
    }
}
