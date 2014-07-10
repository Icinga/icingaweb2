<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;

/**
 * Decorator that wraps form elements with their labels to allow easier styling
 *
 * Labels are drawn for all elements, except hidden, button and submit elements. If you want a
 * placeholder for these elements, set the 'addLabelPlaceholder' property. This can be useful in
 * cases where you want to put inputs with and inputs without labels on the same line and don't
 * want buttons to 'jump'
 */
class ElementWrapper extends Zend_Form_Decorator_Abstract
{
    /**
     * An array of elements that won't get a <label> dom added per default
     *
     * @var array
     */
    protected static $noLabel = array(
        'Zend_Form_Element_Hidden',
        'Zend_Form_Element_Button',
        'Zend_Form_Element_Submit'
    );

    /**
     * Return the DOM for the element label
     *
     * @param   String  $elementName    The name of the element
     *
     * @return  String                  The DOM for the form element's label
     */
    public function getLabel($elementName)
    {
        $label = $this->getElement()->getLabel();
        if (! $label) {
            $label = '&nbsp;';
        }

        if (in_array($this->getElement()->getType(), self::$noLabel)
            && false === $this->getElement()->getAttrib('addLabelPlaceholder', false)) {
            $label = '';
        } else {
            if (in_array($this->getElement()->getType(), self::$noLabel)) {
                $label = '&nbsp;';
            }
            $label = '<label for="' . $elementName . '">' . $label . '</label>';
        }

        return $label;
    }

    /**
     * Render this element
     *
     * @param   String  $content    The content of the form element
     *
     * @return  String              The decorated form element
     */
    public function render($content)
    {
        $elementName = $this->getElement()->getName();
        $label = $this->getLabel($elementName);
        return '<div class="form-element" id="' . $elementName . '-element">' . $label . $content . '</div>';
    }
}
