<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web;

use Icinga\Web\Form;
use Icinga\Test\BaseTestCase;

class FormTest extends BaseTestCase
{
    public function testWhetherAddElementDoesNotAddSpecificDecorators()
    {
        $form = new Form();
        $form->addElement('text', 'someText');
        $element = $form->getElement('someText');

        $this->assertFalse(
            $element->getDecorator('HtmlTag'),
            'Form::addElement does not remove the HtmlTag-Decorator'
        );
        $this->assertFalse(
            $element->getDecorator('Label'),
            'Form::addElement does not remove the Label-Decorator'
        );
        $this->assertFalse(
            $element->getDecorator('DtDdWrapper'),
            'Form::addElement does not remove the DtDdWrapper-Decorator'
        );
    }

    public function testWhetherAddElementDoesNotAddAnyOptionalDecoratorsToHiddenElements()
    {
        $form = new Form();
        $form->addElement('hidden', 'somethingHidden');
        $element = $form->getElement('somethingHidden');

        $this->assertCount(
            1,
            $element->getDecorators(),
            'Form::addElement adds more decorators than necessary to hidden elements'
        );
        $this->assertInstanceOf(
            '\Zend_Form_Decorator_ViewHelper',
            $element->getDecorator('ViewHelper'),
            'Form::addElement does not add the ViewHelper-Decorator to hidden elements'
        );
    }

    public function testWhetherLoadDefaultDecoratorsDoesNotAddTheHtmlTagDecorator()
    {
        $form = new Form();
        $form->loadDefaultDecorators();

        $this->assertArrayNotHasKey(
            'HtmlTag',
            $form->getDecorators(),
            'Form::loadDefaultDecorators adds the HtmlTag-Decorator'
        );
    }
}
