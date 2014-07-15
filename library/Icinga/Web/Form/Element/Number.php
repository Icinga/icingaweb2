<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Element;

use Zend_Form_Element_Xhtml;

/**
 * Number form element
 */
class Number extends Zend_Form_Element_Xhtml
{
    /**
     * Default form view helper to use for rendering
     *
     * @var string
     */
    public $helper = "formNumber";

    /**
     * Check whether $value is of type integer
     *
     * @param   string      $value      The value to check
     * @param   mixed       $context    Context to use
     *
     * @return  bool
     */
    public function isValid($value, $context = null)
    {
        if (parent::isValid($value, $context)) {
            if (is_numeric($value)) {
                return true;
            }

            $this->addError(t('Please enter a number.'));
        }

        return false;
    }
}
