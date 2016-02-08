<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Element;

use Icinga\Web\Request;
use Icinga\Application\Icinga;
use Icinga\Web\Form\FormElement;

/**
 * A button
 */
class Button extends FormElement
{
    /**
     * Use formButton view helper by default
     *
     * @var string
     */
    public $helper = 'formButton';

    /**
     * Constructor
     *
     * @param   string|array|Zend_Config    $spec       Element name or configuration
     * @param   string|array|Zend_Config    $options    Element value or configuration
     */
    public function __construct($spec, $options = null)
    {
        if (is_string($spec) && ((null !== $options) && is_string($options))) {
            $options = array('label' => $options);
        }

        if (!isset($options['ignore'])) {
            $options['ignore'] = true;
        }

        parent::__construct($spec, $options);

        if ($label = $this->getLabel()) {
            // Necessary to get the label shown on the generated HTML
            $this->content = $label;
        }
    }

    /**
     * Validate element value (pseudo)
     *
     * There is no need to reset the value
     *
     * @param   mixed   $value      Is always ignored
     * @param   mixed   $context    Is always ignored
     *
     * @return  bool                Returns always TRUE
     */
    public function isValid($value, $context = null)
    {
        return true;
    }

    /**
     * Has this button been selected?
     *
     * @return  bool
     */
    public function isChecked()
    {
        return $this->getRequest()->getParam($this->getName()) === $this->getValue();
    }

    /**
     * Return the current request
     *
     * @return  Request
     */
    protected function getRequest()
    {
        return Icinga::app()->getRequest();
    }
}
