<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Element;

use Icinga\Web\Form\FormElement;

/**
 * A number input control
 */
class Number extends FormElement
{
    /**
     * Form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'formNumber';

    /**
     * The expected lower bound for the element’s value
     *
     * @var float|null
     */
    protected $min;

    /**
     * The expected upper bound for the element’s
     *
     * @var float|null
     */
    protected $max;

    /**
     * The value granularity of the element’s value
     *
     * Normally, number input controls are limited to an accuracy of integer values.
     *
     * @var float|string|null
     */
    protected $step;

    /**
     * (non-PHPDoc)
     * @see \Zend_Form_Element::init() For the method documentation.
     */
    public function init()
    {
        if ($this->min !== null || $this->max !== null) {
            $this->addValidator('Between', true, array(
                'min'       => $this->min === null ? -INF : $this->min,
                'max'       => $this->max === null ? INF : $this->max,
                'inclusive' => true
            ));
        }
    }

    /**
     * Set the expected lower bound for the element’s value
     *
     * @param   float $min
     *
     * @return  $this
     */
    public function setMin($min)
    {
        $this->min = (float) $min;
        return $this;
    }

    /**
     * Get the expected lower bound for the element’s value
     *
     * @return float|null
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set the expected upper bound for the element’s value
     *
     * @param   float $max
     *
     * @return  $this
     */
    public function setMax($max)
    {
        $this->max = (float) $max;
        return $this;
    }

    /**
     * Get the expected upper bound for the element’s value
     *
     * @return float|null
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set the value granularity of the element’s value
     *
     * @param   float|string $step
     *
     * @return  $this
     */
    public function setStep($step)
    {
        if ($step !== 'any') {
            $step = (float) $step;
        }
        $this->step = $step;
        return $this;
    }

    /**
     * Get the value granularity of the element’s value
     *
     * @return float|string|null
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * (non-PHPDoc)
     * @see \Zend_Form_Element::isValid() For the method documentation.
     */
    public function isValid($value, $context = null)
    {
        $this->setValue($value);
        $value = $this->getValue();
        if ($value !== null && $value !== '' && ! is_numeric($value)) {
            $this->addError(sprintf(t('\'%s\' is not a valid number'), $value));
            return false;
        }
        return parent::isValid($value, $context);
    }
}
