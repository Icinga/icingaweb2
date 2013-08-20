<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath('library/Icinga/Web/Form/BaseFormTest.php');
require_once realpath(__DIR__ . '/../../../../../application/forms/Command/SubmitPassiveCheckResultForm.php');

use \Test\Icinga\Web\Form\BaseFormTest;
use \Icinga\Module\Monitoring\Form\Command\SubmitPassiveCheckResultForm; // Used by constant FORM_CLASS

class SubmitPassiveCheckResultFormTest extends BaseFormTest
{
    const FORM_CLASS = '\Icinga\Module\Monitoring\Form\Command\SubmitPassiveCheckResultForm';

    public function testStateTypes()
    {
        $form = $this->getRequestForm(array(), self::FORM_CLASS);

        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
        $options = $form->getOptions();
        $this->assertCount(4, $options, 'Assert correct number of states in service passive checks form');
        $this->assertEquals('OK', $options[0], 'Assert OK state to be available in service passive check form');
        $this->assertEquals('WARNING', $options[1], 'Assert WARNING state to be available in service passive check form');
        $this->assertEquals('CRITICAL', $options[2], 'Assert CRITICAL state to be available in service passive check form');
        $this->assertEquals('UNKNOWN', $options[3], 'Assert UNKNOWN state to be available in service passive check form');

        $form->setType(SubmitPassiveCheckResultForm::TYPE_HOST);
        $options = $form->getOptions();
        $this->assertCount(3, $options, 'Assert correct number of states in host passive checks form');
        $this->assertEquals('UP', $options[0], 'Assert UP state to be available in host passive check form');
        $this->assertEquals('DOWN', $options[1], 'Assert DOWN state to be available in host passive check form');
        $this->assertEquals('UNREACHABLE', $options[2], 'Assert UNREACHABLE state to be available in host passive check form');
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage Type is not valid
     */
    public function testMissingTypeThrowingException()
    {
        $form = $this->getRequestForm(array(), self::FORM_CLASS);
        $form->buildForm();
    }

    public function testCorrectFormCreation()
    {
        $form = $this->getRequestForm(array(), self::FORM_CLASS);
        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
        $form->buildForm();
    }

    public function testCorrectServicePassiveCheckSubmission()
    {
        $form = $this->getRequestForm(array(
            'pluginstate'     => 0,
            'checkoutput'     => 'DING',
            'performancedata' => '',
            'btn_submit'      => 'foo'
        ), self::FORM_CLASS);

        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Assert a correct passive service check form to pass form validation'
        );
    }

    public function testIncorrectCheckoutputRecognition()
    {
        $form = $this->getRequestForm(array(
            'pluginstate'     => 0,
            'checkoutput'     => '',
            'performancedata' => ''
        ), self::FORM_CLASS);
        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert empty checkoutput to cause validation errors in passive service check '
        );
    }

    public function testIncorrectStateRecognition()
    {
        $form = $this->getRequestForm(array(
            'pluginstate'     => 'LA',
            'checkoutput'     => 'DING',
            'performancedata' => ''
        ), self::FORM_CLASS);
        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert invalid (non-numeric) state to cause validation errors in passive service check'
        );
    }
}
