<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use DateTime;
use Zend_Validate_Abstract;
use Icinga\Util\DateTimeFactory;

/**
 * Validator for date-and-time input controls
 *
 * @see \Icinga\Web\Form\Element\DateTimePicker For the date-and-time input control.
 */
class DateTimeValidator extends Zend_Validate_Abstract
{
    const INVALID_DATETIME_TYPE = 'invalidDateTimeType';
    const INVALID_DATETIME_FORMAT = 'invalidDateTimeFormat';

    /**
     * The messages to write on differen error states
     *
     * @var array
     *
     * @see Zend_Validate_Abstract::$_messageTemplates‚
     */
    protected $_messageTemplates = array(
        self::INVALID_DATETIME_TYPE     => 'Invalid type given. Instance of DateTime or date/time string expected',
        self::INVALID_DATETIME_FORMAT   => 'Date/time string not in the expected format: %value%'
    );

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
            $this->_error(self::INVALID_DATETIME_TYPE);
            return false;
        }

        if (! $value instanceof DateTime) {
            $format = $baseFormat = $this->local === true ? 'Y-m-d\TH:i:s' : DateTime::RFC3339;
            $dateTime = DateTime::createFromFormat($format, $value);

            if ($dateTime === false) {
                $format = substr($format, 0, strrpos($format, ':'));
                $dateTime = DateTime::createFromFormat($format, $value);
            }

            if ($dateTime === false || $dateTime->format($format) !== $value) {
                $this->_error(self::INVALID_DATETIME_FORMAT, DateTimeFactory::create()->format($baseFormat));
                return false;
            }
        }

        return true;
    }
}
