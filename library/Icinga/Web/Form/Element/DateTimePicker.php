<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Element;

use Icinga\Web\Form\Validator\DateTimeValidator;
use \Zend_Form_Element_Text;
use \Zend_Form_Element;
use \Icinga\Util\DateTimeFactory;

/**
 * Datetime form element which returns the input as Unix timestamp after the input has been proven valid. Utilizes
 * DateTimeFactory to ensure time zone awareness
 *
 * @see isValid()
 */
class DateTimePicker extends Zend_Form_Element_Text
{
    /**
     * Default format used my js picker
     *
     * @var string
     */
    public $defaultFormat = 'Y-m-d H:i:s';

    /**
     * JS picker support on or off
     * @var bool
     */
    public $jspicker = true;

    /**
     * View helper to use
     * @var string
     */
    public $helper = 'formDateTime';

    /**
     * The validator used for datetime validation
     * @var DateTimeValidator
     */
    private $dateValidator;

    /**
     * Valid formats to check user input against
     * @var array
     */
    public $patterns = array();

    /**
     * Create a new DateTimePicker
     *
     * @param array|string|\Zend_Config $spec
     * @param null $options
     * @see Zend_Form_Element::__construct()
     */
    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);

        $this->patterns[] = $this->defaultFormat;

        $this->dateValidator = new DateTimeValidator($this->patterns);
        $this->addValidator($this->dateValidator);
    }

    /**
     * Validate filtered date/time strings
     *
     * Expects one or more valid formats being set in $this->patterns. Sets element value as Unix timestamp
     * if the input is considered valid. Utilizes DateTimeFactory to ensure time zone awareness.
     *
     * @param   string  $value
     * @param   mixed   $context
     * @return  bool
     */
    public function isValid($value, $context = null)
    {
        // Overwrite the internal validator to use

        if (!parent::isValid($value, $context)) {
            return false;
        }
        $pattern = $this->dateValidator->getValidPattern();
        if (!$pattern) {
            $this->setValue($value);
            return true;
        }
        $this->setValue(DateTimeFactory::parse($value, $pattern)->getTimestamp());
        return true;
    }

    public function enableJsPicker()
    {
        $this->jspicker = true;
    }

    public function disableJsPicker()
    {
        $this->jspicker = false;
    }
}
