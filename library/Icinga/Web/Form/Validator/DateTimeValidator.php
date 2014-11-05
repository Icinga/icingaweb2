<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Validator;

use DateTime;
use Zend_Validate_Abstract;

/**
 * Validator for date-and-time input controls
 *
 * @see \Icinga\Web\Form\Element\DateTimePicker For the date-and-time input control.
 */
class DateTimeValidator extends Zend_Validate_Abstract
{
    protected $local;

    /**
     * Create a new date-and-time input control validator
     *
     * @param bool $local
     */
    public function __construct($local)
    {
        $this->local = (bool) $local;
    }

    /**
     * Is the date and time valid?
     *
     * @param   string|DateTime $value
     * @param   mixed           $context
     *
     * @return  bool
     *
     * @see     \Zend_Validate_Interface::isValid()
     */
    public function isValid($value, $context = null)
    {
        if (! $value instanceof DateTime && ! is_string($value)) {
            $this->_error(t('Invalid type given. Instance of DateTime or date/time string expected'));
            return false;
        }
        if (is_string($value)) {
            $format = $this->local === true ? 'Y-m-d\TH:i:s' : DateTime::RFC3339;
            $dateTime = DateTime::createFromFormat($format, $value);
            if ($dateTime === false || $dateTime->format($format) !== $value) {
                $this->_error(sprintf(t('Date/time string not in the expected format %s'), $format));
                return false;
            }
        }
        return true;
    }
}
