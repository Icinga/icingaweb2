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
    require_once __DIR__. '/../../../../../application/forms/Command/SubmitPassiveCheckResultForm.php';

    use Monitoring\Form\Command\SubmitPassiveCheckResultForm;
    use \Zend_View;
    use \Zend_Test_PHPUnit_ControllerTestCase;

    class SubmitPassiveCheckResultFormTest extends Zend_Test_PHPUnit_ControllerTestCase
    {
        public function testStateTypes()
        {
            $form = new SubmitPassiveCheckResultForm();
            $form->setRequest($this->getRequest());

            $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
            $options = $form->getOptions();
            $this->assertCount(4, $options);
            $this->assertEquals('OK', $options[0]);
            $this->assertEquals('WARNING', $options[1]);
            $this->assertEquals('CRITICAL', $options[2]);
            $this->assertEquals('UNKNOWN', $options[3]);

            $form->setType(SubmitPassiveCheckResultForm::TYPE_HOST);
            $options = $form->getOptions();
            $this->assertCount(3, $options);
            $this->assertEquals('UP', $options[0]);
            $this->assertEquals('DOWN', $options[1]);
            $this->assertEquals('UNREACHABLE', $options[2]);
        }

        /**
         * @expectedException Icinga\Exception\ProgrammingError
         * @expectedExceptionMessage Type is not valid
         */
        public function testForm1()
        {
            $form = new SubmitPassiveCheckResultForm();
            $form->setRequest($this->getRequest());
            $form->buildForm();
        }

        public function testForm2()
        {
            $form = new SubmitPassiveCheckResultForm();
            $form->setRequest($this->getRequest());
            $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
            $form->buildForm();

            $this->assertCount(6, $form->getElements());
        }

        public function testValidation1()
        {
            $form = new SubmitPassiveCheckResultForm();
            $form->setRequest($this->getRequest());
            $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);

            $this->assertTrue(
                $form->isValid(
                    array(
                        'pluginstate'     => 0,
                        'checkoutput'     => 'DING',
                        'performancedata' => ''
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'pluginstate'     => 0,
                        'checkoutput'     => '',
                        'performancedata' => ''
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'pluginstate'     => 'LA',
                        'checkoutput'     => 'DING',
                        'performancedata' => ''
                    )
                )
            );
        }
    }
}
