<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Validator that checks if a textfield contains a correct date format
 */
class DateFormatValidator extends Zend_Validate_Abstract
{

    /**
     * Valid date characters according to @see http://www.php.net/manual/en/function.date.php
     *
     * @var array
     *
     * @see http://www.php.net/manual/en/function.date.php
     */
    private $validChars =
        array('d', 'D', 'j', 'l', 'N', 'S', 'w', 'z', 'W', 'F', 'm', 'M', 'n', 't', 'L', 'o', 'Y', 'y');

    /**
     * List of sensible time separators
     *
     * @var array
     */
    private $validSeparators = array(' ', ':', '-', '/', ';', ',', '.');

    /**
     * Error templates
     *
     * @var array
     *
     * @see Zend_Validate_Abstract::$_messageTemplates
     */
    protected $_messageTemplates = array(
        'INVALID_CHARACTERS' => 'Invalid date format'
    );

    /**
     * Validate the input value
     *
     * @param   string  $value      The format string to validate
     * @param   null    $context    The form context (ignored)
     *
     * @return  bool True when the input is valid, otherwise false
     *
     * @see     Zend_Validate_Abstract::isValid()
     */
    public function isValid($value, $context = null)
    {
        $rest = trim($value, join(' ', array_merge($this->validChars, $this->validSeparators)));
        if (strlen($rest) > 0) {
            $this->_error('INVALID_CHARACTERS');
            return false;
        }
        return true;
    }
}
