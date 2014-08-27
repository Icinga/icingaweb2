<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use \Icinga\Web\Form\Element\TriStateCheckbox;
use \Icinga\Web\Form;
use \Zend_Form_Element_Hidden;
use \Zend_Form;

/**
 * A form to edit multiple command flags of multiple commands at once. When some commands have
 * different flag values, these flags will be displayed as an undefined state,
 * in a tri-state checkbox.
 */
class MultiCommandFlagForm extends Form {

    /**
     * The Suffix used to mark the old values in the form-field
     *
     * @var string
     */
    const OLD_VALUE_MARKER = '_old_value';

    /**
     * The object properties to change
     *
     * @var array
     */
    private $flags;

    /**
     * The current values of this form
     *
     * @var array
     */
    private $values;

    /**
     * Create a new MultiCommandFlagForm
     *
     * @param array $flags  The flags that will be used. Should contain the
     *                      names of the used property keys.
     */
    public function __construct(array $flags)
    {
        $this->flags = $flags;
        parent::__construct();
        $this->setEnctype(Zend_Form::ENCTYPE_MULTIPART);
    }

    /**
     * Initialise the form values with the array of items to configure.
     *
     * @param mixed     $items    The items that will be edited with this form.
     */
    public function initFromItems($items)
    {
        $this->values = $this->valuesFromObjects($items);
        $this->buildForm();
        $this->populate($this->values);
    }

    /**
     * Return only the values that have been updated.
     */
    public function getChangedValues()
    {
        $values = $this->getValues();
        $changed = array();
        foreach ($values as $key => $value) {
            $oldKey = $key . self::OLD_VALUE_MARKER;
            if (array_key_exists($oldKey, $values)) {
                if ($values[$oldKey] !== $value) {
                    $changed[$key] = $value;
                }
            }
        }
        return $changed;
    }

    private function valuesFromObjects($items)
    {
        $values = array();
        foreach ($items as $item) {
            foreach ($this->flags as $key => $unused) {
                if (isset($item->{$key})) {
                    $value = $item->{$key};
                    // convert strings
                    if ($value === '1' || $value === '0') {
                        $value = intval($value);
                    }
                    // init key with first value
                    if (!array_key_exists($key, $values)) {
                        $values[$key] = $value;
                        continue;
                    }
                    // already a mixed state ?
                    if ($values[$key] === 'unchanged') {
                        continue;
                    }
                    // values differ?
                    if ($values[$key] ^ $value) {
                        $values[$key] = 'unchanged';
                    }
                }
            }
        }
        $old = array();
        foreach ($values as $key => $value) {
            $old[$key . self::OLD_VALUE_MARKER] = $value;
        }
        return array_merge($values, $old);
    }

    public function buildCheckboxes()
    {
        $checkboxes = array();
        foreach ($this->flags as $flag => $description) {
            $checkboxes[] = new TriStateCheckbox(
                $flag,
                array(
                    'label' => $description,
                    'required' => true
                )
            );
        }
        return $checkboxes;
    }

    /**
     * Create the multi flag form
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->setName('form_flag_configuration');
        foreach ($this->buildCheckboxes() as $checkbox) {
            $this->addElement($checkbox);
            $old = new Zend_Form_Element_Hidden($checkbox->getName() . self::OLD_VALUE_MARKER);
            $this->addElement($old);
        }
        $this->setSubmitLabel('Save Configuration');
    }
}
