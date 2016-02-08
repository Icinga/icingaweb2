<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form;

use Zend_Form_Element;
use Icinga\Web\Form;

/**
 * Base class for Icinga Web 2 form elements
 */
class FormElement extends Zend_Form_Element
{
    /**
     * Whether loading default decorators is disabled
     *
     * Icinga Web 2 loads its own default element decorators. For loading Zend's default element decorators set this
     * property to false.
     *
     * @var null|bool
     */
    protected $_disableLoadDefaultDecorators;

    /**
     * Whether loading default decorators is disabled
     *
     * @return bool
     */
    public function loadDefaultDecoratorsIsDisabled()
    {
        return $this->_disableLoadDefaultDecorators === true;
    }

    /**
     * Load default decorators
     *
     * Icinga Web 2 loads its own default element decorators. For loading Zend's default element decorators set
     * FormElement::$_disableLoadDefaultDecorators to false.
     *
     * @return  this
     * @see     Form::$defaultElementDecorators For Icinga Web 2's default element decorators.
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        if (! isset($this->_disableLoadDefaultDecorators)) {
            $decorators = $this->getDecorators();
            if (empty($decorators)) {
                // Load Icinga Web 2's default element decorators
                $this->addDecorators(Form::$defaultElementDecorators);
            }
        } else {
            // Load Zend's default decorators
            parent::loadDefaultDecorators();
        }
        return $this;
    }
}
