<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Validator that interprets the value as a filepath and checks if it's readable
 *
 * This validator should be preferred due to Zend_Validate_File_Exists is
 * getting confused if there is another element in the form called `name'.
 */
class ReadablePathValidator extends Zend_Validate_Abstract
{
    const NOT_READABLE = 'notReadable';
    const DOES_NOT_EXIST = 'doesNotExist';

    /**
     * The messages to write on different error states
     *
     * @var array
     *
     * @see Zend_Validate_Abstract::$_messageTemplates‚
     */
    protected $_messageTemplates = array(
        self::NOT_READABLE      => 'Path is not readable',
        self::DOES_NOT_EXIST    => 'Path does not exist'
    );

    /**
     * Check whether the given value is a readable filepath
     *
     * @param   string  $value      The value submitted in the form
     * @param   mixed   $context    The context of the form
     *
     * @return  bool                Whether the value was successfully validated
     */
    public function isValid($value, $context = null)
    {
        if (false === file_exists($value)) {
            $this->_error(self::DOES_NOT_EXIST);
            return false;
        }

        if (false === is_readable($value)) {
            $this->_error(self::NOT_READABLE);
            return false;
        }

        return true;
    }
}
