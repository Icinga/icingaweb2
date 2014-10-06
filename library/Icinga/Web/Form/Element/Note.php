<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Element;

use Zend_Form_Element;
use Icinga\Web\Form;

/**
 * A note
 */
class Note extends Zend_Form_Element
{
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
     * (non-PHPDoc)
     * @see Zend_Form_Element::init() For the method documentation.
     */
    public function init()
    {
        $this->setDecorators(Form::$defaultElementDecorators);
    }

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
