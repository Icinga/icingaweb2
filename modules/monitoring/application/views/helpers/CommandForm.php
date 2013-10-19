<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
     * @param   array   $arguments      Add parameter as hidden fields
     *
     * @return  Form                    Form to modify
     */
    private function simpleForm($commandName, array $arguments = array())
    {
        $form = new Form();
        $form->setIgnoreChangeDiscarding(true);
        $form->setAttrib('data-icinga-component', 'app/ajaxPostSubmitForm');

        $form->setRequest(Zend_Controller_Front::getInstance()->getRequest());
        $form->setAction($this->view->href('monitoring/command/' . $commandName));

        foreach ($arguments as $elementName => $elementValue) {
            $hiddenField = new Zend_Form_Element_Hidden($elementName);
            $hiddenField->setValue($elementValue);
            $form->addElement($hiddenField);

            $hiddenField = $form->getElement($elementName);
        }

        return $form;
    }

    /**
     * Creates an iconized submit form
     *
     * @param string    $iconCls        Css class of icon
     * @param string    $submitTitle   Title of submit button
     * @param string    $cls           Css class names
     * @param string    $commandName   Name of command
     * @param array     $arguments     Additional arguments
     *
     * @return Form
     */
    public function iconSubmitForm($iconCls, $submitTitle, $cls, $commandName, array $arguments = array())
    {
        $form = $this->labelSubmitForm('', $submitTitle, $cls, $commandName, $arguments);
        $submit = $form->getElement('btn_submit');
        $submit->setLabel(sprintf('<i class="%s"></i>', $iconCls));

        return $form;
    }

    /**
     * Renders a simple for with a labeled submit button
     *
     * @param string $submitLabel   Label of submit button
     * @param string $submitTitle   Title of submit button
     * @param string $cls           Css class names
     * @param string $commandName   Name of command
     * @param array  $arguments     Additional arguments
     *
     * @return Form
     */
    public function labelSubmitForm($submitLabel, $submitTitle, $cls, $commandName, array $arguments = array())
    {
        $form = $this->simpleForm($commandName, $arguments);

        $button = new Zend_Form_Element_Button(
            array(
                'name'      => 'btn_submit',
                'class'     => $this->mergeClass('button btn-common', $cls),
                'escape'    => false,
                'value'     => '1',
                'type'      => 'submit',
                'label'     => $submitLabel,
                'title'     => $submitTitle
            )
        );

        $form->addElement($button);

        // Because of implicit added decorators
        $form->getElement('btn_submit')->setDecorators(array('ViewHelper'));

        return $form;
    }

    public function toggleSubmitForm($label, $checkValue, $enabledCommand, $disabledCommand, array $arguments = array())
    {
        if ($checkValue === '1') {
            $commandName = $disabledCommand;
        } else {
            $commandName = $enabledCommand;
        }

        $form = $this->simpleForm($commandName, $arguments);
        $form->setAttrib('class', 'pull-right');

        $uniqueName = uniqid('check');

        $checkBox = new Zend_Form_Element_Checkbox($uniqueName);

        if ($checkValue === '1') {
            $checkBox->setChecked(true);
        }

        $form->addElement($checkBox);
        $form->enableAutoSubmit(array($uniqueName));

        $checkBox->setDecorators(array('ViewHelper'));
        $checkBox->setAttrib('class', '');
        $checkBox->setAttrib('id', $uniqueName);

        $submit_identifier = new Zend_Form_Element_Hidden('btn_submit');
        $submit_identifier->setValue('1');
        $form->addElement($submit_identifier);
        $form->getElement('btn_submit')->setDecorators(array('ViewHelper'));

        return '<label class="label-horizontal" for="' . $uniqueName . '">' . $label . '</label>' . $form;
    }

    /**
     * Merges css class names together
     *
     * @param   string $base
     * @param   string $additional
     * @param   string ...
     *
     * @return  string
     */
    private function mergeClass($base, $additional)
    {
        $args = func_get_args();
        $base = explode(' ', array_shift($args));
        while (($additional = array_shift($args))) {
            $base = array_merge($base, explode(' ', $additional));
        }
        return implode(' ', $base);
    }
}

// @codingStandardsIgnoreStop