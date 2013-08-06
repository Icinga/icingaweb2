<?php

namespace Tests\Icinga\Web;

require_once('Zend/Form.php');
require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';

require_once('../../library/Icinga/Web/Form.php');
require_once('../../library/Icinga/Exception/ProgrammingError.php');
require_once('../../library/Icinga/Web/Form/InvalidCSRFTokenException.php');

use Icinga\Web\Form;
use \Zend_Test_PHPUnit_ControllerTestCase;


/**
 * Dummy extension class as Icinga\Web\Form is an abstract one
 */
class TestForm extends Form
{
    public function create()
    {
        // pass
    }
}


/**
 * Tests for the Icinga\Web\Form class (Base class for all other forms)
 */
class FormTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    /**
     * Tests whether the cancel label will be added to the form
     */
    function testCancelLabel()
    {
        $form = new TestForm();
        $form->setCancelLabel('Cancel');
        $form->buildForm();
        $this->assertCount(2, $form->getElements(), 'Asserting that the cancel label is present');
    }

    /**
     * Tests whether the submit button will be added to the form
     */
    function testSubmitButton()
    {
        $form = new TestForm();
        $form->setSubmitLabel('Submit');
        $form->buildForm();
        $this->assertCount(2, $form->getElements(), 'Asserting that the submit button is present');
    }

    /**
     * Tests whether automatic form submission will be enabled for a single field
     */
    function testEnableAutoSubmitSingle()
    {
        $form = new TestForm();
        $form->addElement('checkbox', 'example1', array());
        $form->enableAutoSubmit(array('example1'));
        $this->assertArrayHasKey('onchange', $form->getElement('example1')->getAttribs(),
                                 'Asserting that auto-submit got enabled for one element');
    }

    /**
     * Tests whether automatic form submission will be enabled for multiple fields
     */
    function testEnableAutoSubmitMultiple()
    {
        $form = new TestForm();
        $form->addElement('checkbox', 'example1', array());
        $form->addElement('checkbox', 'example2', array());
        $form->enableAutoSubmit(array('example1', 'example2'));
        $this->assertArrayHasKey('onchange', $form->getElement('example1')->getAttribs(),
                                 'Asserting that auto-submit got enabled for multiple elements');
        $this->assertArrayHasKey('onchange', $form->getElement('example2')->getAttribs(),
                                 'Asserting that auto-submit got enabled for multiple elements');
    }

    /**
     * Tests whether automatic form submission can only be enabled for existing elements
     * 
     * @expectedException Icinga\Exception\ProgrammingError
     */
    function testEnableAutoSubmitExisting()
    {
        $form = new TestForm();
        $form->enableAutoSubmit(array('not_existing'));
    }

    /**
     * Tests whether a form will be detected as properly submitted
     */
    function testFormSubmission()
    {
        $form = new TestForm();
        $form->setTokenDisabled();
        $form->setSubmitLabel('foo');
        $request = $this->getRequest();
        $form->setRequest($request->setMethod('GET'));
        $this->assertFalse($form->isSubmittedAndValid(),
                          'Asserting that it is not possible to submit a form not using POST');
        $request->setMethod('POST')->setPost(array('btn_submit' => 'foo'));
        $this->assertTrue($form->isSubmittedAndValid(),
                          'Asserting that it is possible to detect a form as submitted');
    }
}