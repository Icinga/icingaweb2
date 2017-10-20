<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Validator that checks whether a textfield doesn't contain raw double quotes
 */
class UrlValidator extends Zend_Validate_Abstract
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_messageTemplates = array('HAS_QUOTES' => t(
            'The url must not contain raw double quotes. If you really need double quotes, use %22 instead.'
        ));
    }

    /**
     * Validate the input value
     *
     * @param   string  $value      The string to validate
     *
     * @return  bool    true if and only if the input is valid, otherwise false
     *
     * @see     Zend_Validate_Abstract::isValid()
     */
    public function isValid($value)
    {
        $hasQuotes = false === strpos($value, '"');
        if (! $hasQuotes) {
            $this->_error('HAS_QUOTES');
        }
        return $hasQuotes;
    }
}
