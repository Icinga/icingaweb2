<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
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
        $form->setIgnoreChangeDiscarding(true);
        $form->setAttrib('data-icinga-component', 'app/ajaxPostSubmitForm');
        $form->setAttrib('class', 'inline-form');

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
        $form->enableAutoSubmit(array($uniqueName));

        $checkBox->setDecorators(array('ViewHelper'));
        $checkBox->setAttrib('class', '');
        $checkBox->setAttrib('id', $uniqueName);

        $submit_identifier = new Zend_Form_Element_Hidden('btn_submit');
        $submit_identifier->setValue('1');
        $form->addElement($submit_identifier);
        $form->getElement('btn_submit')->setDecorators(array('ViewHelper'));

        $out = '<label class="label-horizontal label-configuration" for="' . $uniqueName . '">'
            . $label
            . '</label>'
            . '<div class="pull-right">';

        if ($changed === true) {
            $out .= '<span class="config-changed">'
                . '<i class="icinga-icon-edit"></i> (modified)'
                . '</span>';
        }

        $formCode = (string) $form;
        
        $jsLessSubmit = '<noscript>'
            . '<input type="submit" value="Change" class="button btn btn-cta" />'
            . '</noscript></form>';

        $formCode = str_replace('</form>', $jsLessSubmit, $formCode);

        $out .= $formCode
            . '</div>';

        return $out;
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