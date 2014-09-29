<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Element;

use Zend_Form_Element;

/**
 * Implements note element for Zend forms
 */
class Note extends Zend_Form_Element
{
    /**
     * Disable default decorators
     *
     * \Icinga\Web\Form sets default decorators for elements.
     *
     * @var bool
     *
     * @see \Icinga\Web\Form::__construct() For default element decorators.
     */
    protected $_disableLoadDefaultDecorators = true;

    /**
     * Name of the view helper
     *
     * @var string
     */
    public $helper = 'formNote';

    /**
     * Return true to ensure redrawing
     *
     * @param mixed $value      The value of to validate (ignored)
     * @return bool             Always true
     */
    public function isValid($value)
    {
        return true;
    }
}
