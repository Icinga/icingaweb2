<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
     * The expected lower bound for the element’s value
     *
     * @var DateTime|null
     */
    protected $min;

    /**
     * The expected upper bound for the element’s
     *
     * @var DateTime|null
     */
    protected $max;

    /**
     * (non-PHPDoc)
     * @see Zend_Form_Element::init() For the method documentation.
     */
    public function init()
    {
        $this->addValidator(
            new DateTimeValidator($this->local), true  // true for breaking the validator chain on failure
        );
        if ($this->min !== null) {
            $this->addValidator('GreaterThan', true, array('min' => $this->min));
        }
        if ($this->max !== null) {
            $this->addValidator('LessThan', true, array('max' => $this->max));
        }
    }

    public function setLocal($local)
    {
        $this->local = (bool) $local;
        return $this;
    }

    public function getLocal()
    {
        return $this->local;
    }

    /**
     * Set the expected lower bound for the element’s value
     *
     * @param   DateTime $min
     *
     * @return  $this
     */
    public function setMin(DateTime $min)
    {
        $this->min = $min;
        return $this;
    }

    /**
     * Get the expected lower bound for the element’s value
     *
     * @return DateTime|null
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set the expected upper bound for the element’s value
     *
     * @param   DateTime $max
     *
     * @return  $this
     */
    public function setMax(DateTime $max)
    {
        $this->max = $max;
        return $this;
    }

    /**
     * Get the expected upper bound for the element’s value
     *
     * @return DateTime|null
     */
    public function getMax()
    {
        return $this->max;
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
            $format = $this->local === true ? 'Y-m-d\TH:i:s' : DateTime::RFC3339;
            $dateTime = DateTime::createFromFormat($format, $value);
            if ($dateTime === false) {
                $dateTime = DateTime::createFromFormat(substr($format, 0, strrpos($format, ':')), $value);
            }

            $this->setValue($dateTime);
        }

        return true;
    }
}
