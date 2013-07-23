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
    require_once __DIR__. '/../../../../../application/forms/Command/CommentForm.php';

    use Monitoring\Form\Command\CommentForm;
    use \Zend_View;
    use \Zend_Test_PHPUnit_ControllerTestCase;

    class CommentFormTest extends Zend_Test_PHPUnit_ControllerTestCase
    {
        public function testForm()
        {
            $form = new CommentForm();
            $form->setRequest($this->getRequest());
            $form->buildForm();

            $this->assertCount(6, $form->getElements());
        }

        public function testValidation()
        {
            $form = new CommentForm();
            $form->setRequest($this->getRequest());

            $this->assertTrue(
                $form->isValid(
                    array(
                        'author'  => 'test1',
                        'comment' => 'test2',
                        'sticky'  => '0'
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'  => 'test1',
                        'comment' => '',
                        'sticky'  => '0'
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'  => '',
                        'comment' => 'test2',
                        'sticky'  => '0'
                    )
                )
            );
        }
    }
}
