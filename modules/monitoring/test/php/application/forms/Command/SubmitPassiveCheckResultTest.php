<?php

namespace Test\Monitoring\Forms\Command;

require_once __DIR__. '/BaseFormTest.php';
require_once __DIR__. '/../../../../../application/forms/Command/ConfirmationForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/WithChildrenCommandForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/SubmitPassiveCheckResultForm.php';


use \Zend_View;
use \Zend_Test_PHPUnit_ControllerTestCase;
use Monitoring\Form\Command\SubmitPassiveCheckResultForm;

class SubmitPassiveCheckResultFormTest extends BaseFormTest
{
    const FORMCLASS = "Monitoring\Form\Command\SubmitPassiveCheckResultForm";
    public function testStateTypes()
    {
        $form = $this->getRequestForm(array(), self::FORMCLASS);

        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
        $options = $form->getOptions();
        $this->assertCount(4, $options, "Assert correct number of states in service passive checks form");
        $this->assertEquals('OK', $options[0], "Assert OK state to be available in service passive check form");
        $this->assertEquals('WARNING', $options[1], "Assert WARNING state to be available in service passive check form");
        $this->assertEquals('CRITICAL', $options[2], "Assert CRITICAL state to be available in service passive check form");
        $this->assertEquals('UNKNOWN', $options[3], "Assert UNKNOWN state to be available in service passive check form");

        $form->setType(SubmitPassiveCheckResultForm::TYPE_HOST);
        $options = $form->getOptions();
        $this->assertCount(3, $options, "Assert correct number of states in host passive checks form");
        $this->assertEquals('UP', $options[0], "Assert UP state to be available in host passive check form");
        $this->assertEquals('DOWN', $options[1], "Assert DOWN state to be available in host passive check form");
        $this->assertEquals('UNREACHABLE', $options[2], "Assert UNREACHABLE state to be available in host passive check form");
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage Type is not valid
     */
    public function testMissingTypeThrowingException()
    {
        $form = $this->getRequestForm(array(), self::FORMCLASS);
        $form->buildForm();
    }

    public function testCorrectFormCreation()
    {
        $form = $this->getRequestForm(array(), self::FORMCLASS);
        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
        $form->buildForm();

        $this->assertCount(6, $form->getElements(), "Assert correct number of elements in form");
    }

    public function testCorrectServicePassiveCheckSubmission()
    {
        $form = $this->getRequestForm(array(
            'pluginstate'     => 0,
            'checkoutput'     => 'DING',
            'performancedata' => ''
        ), self::FORMCLASS);

        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);

        $this->assertTrue(
            $form->isPostAndValid(),
            "Assert a correct passive service check form to pass form validation"
        );
    }

    public function testIncorrectCheckoutputRecognition()
    {
        $form = $this->getRequestForm(array(
            'pluginstate'     => 0,
            'checkoutput'     => '',
            'performancedata' => ''
        ), self::FORMCLASS);
        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);

        $this->assertFalse(
            $form->isPostAndValid(),
            "Assert empty checkoutput to cause validation errors in passive service check "
        );
    }

    public function testIncorrectStateRecognition()
    {
        $form = $this->getRequestForm(array(
            'pluginstate'     => 'LA',
            'checkoutput'     => 'DING',
            'performancedata' => ''
        ), self::FORMCLASS);
        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);

        $this->assertFalse(
            $form->isPostAndValid(),
            "Assert invalid (non-numeric) state to cause validation errors in passive service check"
        );
    }
}

