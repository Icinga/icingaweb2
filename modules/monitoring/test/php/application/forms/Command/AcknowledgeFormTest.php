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

    require_once __DIR__. '/../../../../../../../library/Icinga/Exception/ProgrammingError.php';
    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form.php';
    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form/Element/Note.php';
    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form/Element/DateTime.php';
    require_once __DIR__. '/../../../../../application/forms/Command/ConfirmationForm.php';
    require_once __DIR__. '/../../../../../application/forms/Command/WithChildrenCommandForm.php';
    require_once __DIR__. '/../../../../../application/forms/Command/AcknowledgeForm.php';

    use Monitoring\Form\Command\AcknowledgeForm;
    use \Zend_View;
    use \Zend_Test_PHPUnit_ControllerTestCase;

    class AcknowledgeFormTest extends Zend_Test_PHPUnit_ControllerTestCase
    {
        public function testForm()
        {
            $form = new AcknowledgeForm();
            $form->setRequest($this->getRequest());
            $form->buildForm();

            $this->assertCount(11, $form->getElements());
        }

        public function testValidation1()
        {
            $form = new AcknowledgeForm();
            $form->setRequest($this->getRequest());

            $this->assertTrue(
                $form->isValid(
                    array(
                        'author'     => 'test1',
                        'comment'    => 'test comment',
                        'persistent' => '0',
                        'expire'     => '0',
                        'expiretime' => '',
                        'sticky'     => '0',
                        'notify'     => '0'
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'     => 'test1',
                        'comment'    => '',
                        'persistent' => '0',
                        'expire'     => '0',
                        'expiretime' => '',
                        'sticky'     => '0',
                        'notify'     => '0'
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'     => 'test1',
                        'comment'    => 'test comment',
                        'persistent' => '0',
                        'expire'     => '1',
                        'expiretime' => '',
                        'sticky'     => '0',
                        'notify'     => '0'
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'     => 'test1',
                        'comment'    => 'test comment',
                        'persistent' => '0',
                        'expire'     => '1',
                        'expiretime' => 'NOT A DATE',
                        'sticky'     => '0',
                        'notify'     => '0'
                    )
                )
            );

            $this->assertTrue(
                $form->isValid(
                    array(
                        'author'     => 'test1',
                        'comment'    => 'test comment',
                        'persistent' => '0',
                        'expire'     => '1',
                        'expiretime' => '2013-07-10 17:32:16',
                        'sticky'     => '0',
                        'notify'     => '0'
                    )
                )
            );
        }
    }
}
