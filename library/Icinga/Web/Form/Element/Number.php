<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Element;

use Zend_Form_Element;

/**
 * A number input control
 */
class Number extends Zend_Form_Element
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
    public $helper = 'formNumber';

    /**
     * (non-PHPDoc)
     * @see \Zend_Form_Element::init() For the method documentation.
     */
    public function init()
    {
        $this->addValidator('Int');
    }
}
