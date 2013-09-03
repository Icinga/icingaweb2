<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Test/BaseTestCase.php');

use Icinga\Test\BaseTestCase;

require_once BaseTestCase::$libDir . '/Exception/ProgrammingError.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/CommandForm.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/SubmitPassiveCheckResultForm.php';

use Icinga\Module\Monitoring\Form\Command\SubmitPassiveCheckResultForm;

class SubmitPassiveCheckResultFormTest extends BaseTestCase
{
    const FORM_CLASS = 'Icinga\Module\Monitoring\Form\Command\SubmitPassiveCheckResultForm';

    public function testStateTypes()
    {
        $form = $this->createForm(self::FORM_CLASS, array());

        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
        $options = $form->getOptions();
        $this->assertCount(4, $options, 'Assert correct number of states in service passive checks form');
        $this->assertEquals('OK', $options[0], 'Assert OK state to be available in service passive check form');
        $this->assertEquals(
            'WARNING',
            $options[1],
            'Assert WARNING state to be available in service passive check form'
        );
        $this->assertEquals(
            'CRITICAL',
            $options[2],
            'Assert CRITICAL state to be available in service passive check form'
        );
        $this->assertEquals(
            'UNKNOWN',
            $options[3],
            'Assert UNKNOWN state to be available in service passive check form'
        );
        $form->setType(SubmitPassiveCheckResultForm::TYPE_HOST);
        $options = $form->getOptions();
        $this->assertCount(3, $options, 'Assert correct number of states in host passive checks form');
        $this->assertEquals('UP', $options[0], 'Assert UP state to be available in host passive check form');
        $this->assertEquals('DOWN', $options[1], 'Assert DOWN state to be available in host passive check form');
        $this->assertEquals(
            'UNREACHABLE',
            $options[2],
            'Assert UNREACHABLE state to be available in host passive check form'
        );
    }

    /**
     * @expectedException           Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage    Type is not valid
     */
    public function testMissingTypeThrowingException()
    {
        $form = $this->createForm(self::FORM_CLASS, array());
        $form->buildForm();
    }

    public function testCorrectFormCreation()
    {
        $form = $this->createForm(self::FORM_CLASS, array());
        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
        $form->buildForm();
    }

    public function testCorrectServicePassiveCheckSubmission()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'pluginstate'     => 0,
                'checkoutput'     => 'DING',
                'performancedata' => '',
                'btn_submit'      => 'foo'
            )
        );
        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Assert a correct passive service check form to pass form validation'
        );
    }

    public function testIncorrectCheckoutputRecognition()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'pluginstate'     => 0,
                'checkoutput'     => '',
                'performancedata' => ''
            )
        );
        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert empty checkoutput to cause validation errors in passive service check '
        );
    }

    public function testIncorrectStateRecognition()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'pluginstate'     => 'LA',
                'checkoutput'     => 'DING',
                'performancedata' => ''
            )
        );
        $form->setType(SubmitPassiveCheckResultForm::TYPE_SERVICE);
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert invalid (non-numeric) state to cause validation errors in passive service check'
        );
    }
}
