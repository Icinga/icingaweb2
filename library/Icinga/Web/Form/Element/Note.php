<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Element;

use Zend_Form_Element;

/**
 * A note
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
     * Form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'formNote';

    /**
     * Ignore element when retrieving values at form level
     *
     * @var bool
     */
    protected $_ignore = true;

    /**
     * Validate element value (pseudo)
     *
     * @param   mixed $value    Ignored
     *
     * @return  bool            Always true
     */
    public function isValid($value)
    {
        return true;
    }
}
