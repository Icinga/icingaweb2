<?php
namespace Test\Monitoring\Forms\Command;

require_once  realpath('library/Icinga/Web/Form/BaseFormTest.php');
require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form.php';
require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form/Element/Note.php';
require_once __DIR__. '/../../../../../application/forms/Command/CommandForm.php';


use \Zend_View;
use \Test\Icinga\Web\Form\BaseFormTest;
use \Icinga\Module\Monitoring\Form\Command\CommandForm;

class CommandFormTest extends BaseFormTest
{
    public function testFormCreation()
    {
        $view = new Zend_View();
        $form = new CommandForm();

        $form->setRequest($this->getRequest());

        $form->addNote('444 NOTE 1');
        $form->addNote('555 NOTE 2');
        $form->buildForm();
        $content = $form->render($view);

        $this->assertContains('<dd id="note_0-element">', $content);
        $this->assertContains('<dd id="note_1-element">', $content);
        $this->assertContains('444 NOTE 1</dd>', $content);
        $this->assertContains('555 NOTE 2</dd>', $content);
    }
}
