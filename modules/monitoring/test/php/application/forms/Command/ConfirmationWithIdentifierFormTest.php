<?php


namespace Test\Monitoring\Forms\Command;

require_once  realpath('library/Icinga/Web/Form/BaseFormTest.php');
require_once __DIR__. '/../../../../../application/forms/Command/CommandForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/WithChildrenCommandForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/CommandWithIdentifierForm.php';

use \Icinga\Module\Monitoring\Form\Command\CommandWithIdentifierForm;
use \Zend_View;
use \Zend_Test_PHPUnit_ControllerTestCase;
use \Test\Icinga\Web\Form\BaseFormTest;

class CommandWithIdentifierFormTest extends BaseFormTest
{
    const FORMCLASS = "Monitoring\Form\Command\CommandWithIdentifierForm";
    public function testForm()
    {
        $form = $this->getRequestForm(array(), self::FORMCLASS);
        $form->setSubmitLabel('DING DING');
        $form->buildForm();

        $this->assertCount(4, $form->getElements());
    }

    public function testCorrectFormValidation()
    {

        $form = $this->getRequestForm(array(
            'testval'    => 123,
            'btn_submit' => 'foo'
        ), self::FORMCLASS);

        $form->setFieldLabel('Test1');
        $form->setFieldName('testval');
        $form->setSubmitLabel('DING DING');

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            "Asserting correct confirmation with id to be valid"
        );
    }

    public function testInvalidValueValidationErrors()
    {
        $form = $this->getRequestForm(array(
            'testval' => ''
        ), self::FORMCLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            "Asserting an invalid (empty) value to cause validation errors"
        );
    }

    public function testNonNumericValueValidationErrors()
    {
        $form = $this->getRequestForm(array(
            'testval' => 'NaN'
        ), self::FORMCLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            "Asserting an non numeric value to cause validation errors"
        );
    }

    public function testRequestBridge()
    {
        $form = $this->getRequestForm(array(
            'objectid' => 123123666
        ), self::FORMCLASS);
        $form->buildForm();

        $this->assertTrue($form->isSubmittedAndValid());

        $this->assertEquals('123123666', $form->getElement('objectid')->getValue());
    }
}
