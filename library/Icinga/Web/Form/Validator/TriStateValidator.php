<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;

class TriStateValidator extends Zend_Validate_Abstract
{
    /**
     * @var null
     */
    private $validPattern = null;

    /**
     * Validate the input value and set the value of @see validPattern if the input machtes
     * a state description like '0', '1' or 'unchanged'
     *
     * @param   string  $value      The value to validate
     * @param   null    $context    The form context (ignored)
     *
     * @return  bool True when the input is valid, otherwise false
     *
     * @see     Zend_Validate_Abstract::isValid()
     */
    public function isValid($value, $context = null)
    {
        if (!is_string($value) && !is_int($value)) {
            $this->error('INVALID_TYPE');
            return false;
        }

        if (is_string($value)) {
            $value = intval($value);
            if ($value === 'unchanged') {
                $this->validPattern = null;
                return true;
            }
        }

        if (is_int($value)) {
            if ($value === 1 || $value === 0) {
                $this->validPattern = $value;
                return true;
            }
        }
        return false;
    }

    public function getValidPattern()
    {
        return $this->validPattern;
    }
}
