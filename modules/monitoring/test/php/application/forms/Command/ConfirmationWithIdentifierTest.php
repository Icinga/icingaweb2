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
    require_once __DIR__. '/../../../../../application/forms/Command/AbstractCommand.php';
    require_once __DIR__. '/../../../../../application/forms/Command/WithChildrenCommand.php';
    require_once __DIR__. '/../../../../../application/forms/Command/ConfirmationWithIdentifier.php';

    use Monitoring\Form\Command\ConfirmationWithIdentifier;
    use \Zend_View;
    use \Zend_Test_PHPUnit_ControllerTestCase;

    class ConfirmationWithIdentifierTest extends Zend_Test_PHPUnit_ControllerTestCase
    {
        public function testForm()
        {
            $form = new ConfirmationWithIdentifier();
            $form->setRequest($this->getRequest());
            $form->setSubmitLabel('DING DING');
            $form->buildForm();

            $this->assertCount(4, $form->getElements());
        }

        public function testValidation()
        {
            $form = new ConfirmationWithIdentifier();
            $form->setRequest($this->getRequest());
            $form->setFieldLabel('Test1');
            $form->setFieldName('testval');
            $form->setSubmitLabel('DING DING');

            $this->assertTrue(
                $form->isValid(
                    array(
                        'testval' => 123
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'testval' => ''
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'testval' => 'NaN'
                    )
                )
            );
        }

        public function testRequestBridge()
        {
            $this->getRequest()->setPost(
                array(
                    'objectid' => 123123666
                )
            );

            $form = new ConfirmationWithIdentifier();
            $form->setRequest($this->getRequest());
            $form->buildForm();

            $this->assertTrue($form->isValid(null));
            $this->assertTrue($form->isValid($this->getRequest()));

            $this->assertEquals('123123666', $form->getElement('objectid')->getValue());
        }
    }
}
