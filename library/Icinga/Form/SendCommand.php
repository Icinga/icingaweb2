<?php
namespace Icinga\Form;

use Icinga\Form\Builder;
use Icinga\Form\Elements\Date;
use Icinga\Form\Elements\Time;

class SendCommand extends Builder
{
    public function __construct($message)
    {
        parent::__construct();
        $this->message = $message;
        $this->init();
    }

    public function init()
    {
        $this->setMethod("post");

        $note = new \Zend_Form_Element_Note("note");
        $note->setValue($this->message);
        $this->addElement($note);

        $this->addElement("hidden", "servicelist");
        $this->addElement("hidden", "hostlist");
    }

    public function setHosts($hosts)
    {
        $this->setDefault("hostlist", $hosts);
    }

    public function getHosts()
    {
        return $this->getValue("hostlist");
    }

    public function setServices($services)
    {
        $this->setDefault("servicelist", $services);
    }

    public function getServices()
    {
        return $this->getValue("servicelist");
    }

    public function addCheckbox($id, $label, $checked)
    {
        $this->addElement("checkbox", $id, array(
            'checked' => $checked,
            'label' => $label
            )
        );
    }

    public function isChecked($id)
    {
        return $this->getElement($id)->isChecked();
    }

    public function addSubmitButton($label)
    {
        $this->addElement("submit", "btn_submit", array(
            'label' => $label
            )
        );
    }

    public function addDatePicker($id, $label, $value = "")
    {
        $date = new Date($id);
        $date->setValue($value);
        $this->addElement($date, $id, array(
            'label' => $label
            )
        );
    }

    public function getDate($id)
    {
        return $this->getValue($id);
    }

    public function addTimePicker($id, $label, $value = "")
    {
        $time = new Time($id);
        $time->setValue($value);
        $this->addElement($time, $id, array(
            'label' => $label
            )
        );
    }

    public function getTime($id)
    {
        return $this->getValue($id);
    }

    public function addTextBox($id, $label, $value = "", $readonly = false, $multiline = false)
    {
        $options = array('label' => $label, 'value' => $value);
        if ($readonly) {
            $options['readonly'] = 1;
        }
        $this->addElement($multiline ? "textarea" : "text", $id, $options);
    }

    public function getText($id)
    {
        return $this->getValue($id);
    }

    public function addChoice($id, $label, $values)
    {
        $this->addElement("select", $id, array(
            'label' => $label
            )
        );
        $this->getElement($id)->setMultiOptions($values);
    }

    public function getChoice($id)
    {
        return $this->getElement($id)->options[$_POST[$id]];
    }
}

?>