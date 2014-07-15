<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Validator;

use \Zend_Validate_Abstract;
use \Icinga\Application\Config as IcingaConfig;

/**
 * Validator that interprets the value as a path and checks if it's writable
 */
class WritablePathValidator extends Zend_Validate_Abstract
{
    /**
     * The messages to write on differen error states
     *
     * @var array
     *
     * @see Zend_Validate_Abstract::$_messageTemplates‚
     */
    protected $_messageTemplates = array(
        'NOT_WRITABLE'      =>  'Path is not writable',
        'DOES_NOT_EXIST'    =>  'Path does not exist'
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
            $this->_error('DOES_NOT_EXIST');
            return false;
        }

        if ((file_exists($value) && is_writable($value)) ||
            (is_dir(dirname($value)) && is_writable(dirname($value)))
        ) {
            return true;
        }
        $this->_error('NOT_WRITABLE');
        return false;
    }
}
