<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Form;

/**
 * Helper to build inline html command forms
 */
class Zend_View_Helper_CommandForm extends Zend_View_Helper_Abstract
{
    private static $getArguments = array(
        'host',
        'service',
        'global',
        'commentid',
        'downtimeid'
    );

    /**
     * Creates a simple form without additional input fields
     *
     * @param   string  $commandName    Name of command (icinga 2 web name)
     * @param   array   $arguments      Add parameter as hidden fields
     *
     * @return  Form                    Form to modify
     */
    public function simpleForm($commandName, array $arguments = array())
    {
        $form = new Form();
        $form->setAttrib('class', 'inline');
        $form->setRequest(Zend_Controller_Front::getInstance()->getRequest());

        // Filter work only from get parts. Put important
        // fields in the action URL
        $getParts = array();
        foreach (self::$getArguments as $argumentName) {
            if (array_key_exists($argumentName, $arguments) === true) {
                if ($arguments[$argumentName]) {
                    $getParts[$argumentName] = $arguments[$argumentName];
                }

                unset($arguments[$argumentName]);
            }
        }

        $form->setAction($this->view->href('monitoring/command/' . $commandName, $getParts));

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
    public function iconSubmitForm($icon, $submitTitle, $cls, $commandName, array $arguments = array())
    {
        $form = $this->labelSubmitForm('', $submitTitle, $cls, $commandName, $arguments);
        $submit = $form->getElement('btn_submit');
        $submit->setLabel($this->view->img($icon));

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
    public function labelSubmitForm($submitLabel, $submitTitle, $cls = '', $commandName, array $arguments = array())
    {
        $form = $this->simpleForm($commandName, $arguments);

        $button = new Zend_Form_Element_Submit(
            array(
                'name'      => 'btn_submit',
                'class'     => $cls,
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

    /**
     * Create a toggle form for switch between commands
     *
     * @param   string  $label
     * @param   string  $checkValue
     * @param   string  $enabledCommand
     * @param   string  $disabledCommand
     * @param   bool    $changed
     * @param   array   $arguments
     *
     * @return  string
     */
    public function toggleSubmitForm($label, $checkValue, $enabledCommand, $disabledCommand, $changed = false, array $arguments = array())
    {
        if ($checkValue === '1') {
            $commandName = $disabledCommand;
        } else {
            $commandName = $enabledCommand;
        }

        $form = $this->simpleForm($commandName, $arguments);

        $uniqueName = uniqid('check');

        $checkBox = new Zend_Form_Element_Checkbox($uniqueName);

        if ($checkValue === '1') {
            $checkBox->setChecked(true);
        }

        $form->addElement($checkBox);

        $checkBox->setDecorators(array('ViewHelper'));
        $checkBox->setAttrib('id', $uniqueName);
        $form->enableAutoSubmit(array($uniqueName));

        $submit_identifier = new Zend_Form_Element_Hidden('btn_submit');
        $submit_identifier->setValue('1');
        $form->addElement($submit_identifier);
        $form->getElement('btn_submit')->setDecorators(array('ViewHelper'));

        $out = '';
        if ($label) {
            $out .= '<label for="' . $uniqueName . '">'
                . $label
                . '</label>';
        }

        if ($changed === true) {
            $out .= ' (modified)';
        }

        $formCode = (string) $form;

        $jsLessSubmit = '<noscript>'
            . '<input type="submit" value="Change" class="button" />'
            . '</noscript></form>';

        $formCode = str_replace('</form>', $jsLessSubmit, $formCode);

        $out .= $formCode;

        return $out;
    }
}
