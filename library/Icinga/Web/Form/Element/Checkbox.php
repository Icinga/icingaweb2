<?php
/* Icinga Web 2 | (c) 2019 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Element;

class Checkbox extends \Zend_Form_Element_Checkbox
{
    public function loadDefaultDecorators()
    {
        parent::loadDefaultDecorators();

        if (! $this->loadDefaultDecoratorsIsDisabled()) {
            $class = 'toggle-switch';
            if ($this->getAttrib('disabled')) {
                $class .= ' disabled';
            }
            $currentDecorators = $this->getDecorators();
            $pos = array_search('Zend_Form_Decorator_ViewHelper', array_keys($currentDecorators), true);
            $decorators = array_slice($currentDecorators, 0, $pos);
            $decorators += [
                'ToggleSwitchOpen' => [
                    ['ToggleSwitchOpen' => 'HtmlTag'],
                    ['tag' => 'label', 'class' => $class, 'openOnly' => true, 'placement' => 'append']
                ]
            ];
            $decorators += array_slice($currentDecorators, $pos, 1);
            $decorators += [
                'ToggleSlider'      => [
                    ['ToggleSlider' => 'HtmlTag'],
                    ['tag' => 'span', 'class' => 'toggle-slider', 'placement' => 'append']
                ],
                'ToggleSwitchClose' => [
                    ['ToggleSwitchClose' => 'HtmlTag'],
                    ['tag' => 'label', 'closeOnly' => true, 'placement' => 'append']
                ]
            ];
            $decorators += array_slice($currentDecorators, $pos + 1);
            $this->setDecorators($decorators);
        }

        return $this;
    }
}
