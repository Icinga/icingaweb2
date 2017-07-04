<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Element;

use DateTime;
use Icinga\Web\Form\FormElement;
use Icinga\Web\Form\Validator\DateTimeValidator;

/**
 * A date-and-time input control
 */
class DateTimePicker extends FormElement
{
    /**
     * Form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'formDateTime';

    /**
     * @var bool
     */
    protected $local = true;

    /**
     * (non-PHPDoc)
     * @see Zend_Form_Element::init() For the method documentation.
     */
    public function init()
    {
        $this->addValidator(
            new DateTimeValidator($this->local),
            true  // true for breaking the validator chain on failure
        );
    }

    /**
     * Get the expected date and time format of any user input
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->local ? 'Y-m-d\TH:i:s' : DateTime::RFC3339;
    }

    /**
     * Is the date and time valid?
     *
     * @param   string|DateTime     $value
     * @param   mixed               $context
     *
     * @return  bool
     */
    public function isValid($value, $context = null)
    {
        if (! parent::isValid($value, $context)) {
            return false;
        }

        if (! $value instanceof DateTime) {
            $format = $this->getFormat();
            $dateTime = DateTime::createFromFormat($format, $value);
            if ($dateTime === false) {
                $dateTime = DateTime::createFromFormat(substr($format, 0, strrpos($format, ':')), $value);
            }

            $this->setValue($dateTime);
        }

        return true;
    }
}
