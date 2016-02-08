<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Validator that interprets the value as a path and checks if it's writable
 */
class WritablePathValidator extends Zend_Validate_Abstract
{
    const NOT_WRITABLE = 'notWritable';
    const DOES_NOT_EXIST = 'doesNotExist';

    /**
     * The messages to write on differen error states
     *
     * @var array
     *
     * @see Zend_Validate_Abstract::$_messageTemplates‚
     */
    protected $_messageTemplates = array(
        self::NOT_WRITABLE      => 'Path is not writable',
        self::DOES_NOT_EXIST    => 'Path does not exist'
    );

    /**
     * When true, the file or directory must exist
     *
     * @var bool
     */
    private $requireExistence = false;

    /**
     * Set this validator to require the target file to exist
     */
    public function setRequireExistence()
    {
        $this->requireExistence = true;
    }

    /**
     * Check whether the given value is writable path
     *
     * @param   string  $value      The value submitted in the form
     * @param   mixed   $context    The context of the form
     *
     * @return  bool True when validation worked, otherwise false
     *
     * @see     Zend_Validate_Abstract::isValid()
     */
    public function isValid($value, $context = null)
    {
        $value = (string) $value;

        $this->_setValue($value);
        if ($this->requireExistence && !file_exists($value)) {
            $this->_error(self::DOES_NOT_EXIST);
            return false;
        }

        if ((file_exists($value) && is_writable($value)) ||
            (is_dir(dirname($value)) && is_writable(dirname($value)))
        ) {
            return true;
        }

        $this->_error(self::NOT_WRITABLE);
        return false;
    }
}
