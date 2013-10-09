<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Icinga;
use Icinga\Web\Form;

/**
 * Helper to build inline html command forms
 */
class Zend_View_Helper_CommandForm extends Zend_View_Helper_Abstract
{
    /**
     * Creates a simple form without additional input fields
     *
     * @param   string  $commandName    Name of command (icinga 2 web name)
     * @param   string  $submitLabel    Label of submit button
     * @param   array   $arguments      Add parameter as hidden fields
     *
     * @return  string                  Html form content
     */
    public function simpleForm($commandName, $submitLabel, array $arguments = array())
    {
        $form = new Form();

        $form->setIgnoreChangeDiscarding(true);
        $form->setAttrib('data-icinga-component', 'app/ajaxPostSubmitForm');

        $form->setRequest(Zend_Controller_Front::getInstance()->getRequest());
        $form->setSubmitLabel($submitLabel !== null ? $submitLabel : 'Submit');
        $form->setAction($this->view->href('monitoring/command/' . $commandName));

        foreach ($arguments as $elementName => $elementValue) {
            $hiddenField = new Zend_Form_Element_Hidden($elementName);
            $hiddenField->setValue($elementValue);
            $form->addElement($hiddenField);
        }

        return $form->render();
    }
}

// @codingStandardsIgnoreStop