<?php
namespace Test\Monitoring\Forms\Command;

require_once __DIR__.'/BaseFormTest.php';
require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form.php';
require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form/Element/Note.php';
require_once __DIR__. '/../../../../../application/forms/Command/ConfirmationForm.php';


use \Zend_View;
use \Zend_Test_PHPUnit_ControllerTestCase;
use Monitoring\Form\Command\ConfirmationForm;

class ConfirmationFormTest extends BaseFormTest
{
    public function testFormCreation()
    {
        $view = new Zend_View();
        $form = new ConfirmationForm();

        $form->setRequest($this->getRequest());

        $form->setSubmitLabel('111TEST_SUBMIT');

        $form->setCancelLabel('888TEST_RESET');

        $form->addNote('444 NOTE 1');
        $form->addNote('555 NOTE 2');
        $form->buildForm();
        $content = $form->render($view);

        $this->assertContains('<input type="submit" name="submit" id="submit" value="111TEST_SUBMIT" class="btn btn-primary pull-right">', $content);
        $this->assertContains('<input type="reset" name="reset" id="reset" value="888TEST_RESET" class="btn pull-right"></dd>', $content);
        $this->assertContains('<dd id="note_0-element">', $content);
        $this->assertContains('<dd id="note_1-element">', $content);
        $this->assertContains('444 NOTE 1</dd>', $content);
        $this->assertContains('555 NOTE 2</dd>', $content);
    }

    public function testFormNotes()
    {
        $form = new ConfirmationForm();
        $form->addNote('test1');
        $form->addNote('test2');

        $reference = array('test1', 'test2');
        $this->assertCount(2, $form->getNotes());
        $this->assertEquals($reference, $form->getNotes());

        $form->clearNotes();
        $this->assertCount(0, $form->getNotes());
    }
}
