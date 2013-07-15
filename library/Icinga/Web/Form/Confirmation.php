<?php
namespace Icinga\Form;

use Icinga\Form\Builder;
use Icinga\Exception\ProgrammingError;

class Confirmation extends Builder
{
    const YES_NO = 0;
    const OK_CANCEL = 1;

    public function __construct($message, $style)
    {
        parent::__construct();
        $this->message = $message;
        $this->style = $style;
        $this->init();
    }

    public function init()
    {
        $this->setMethod("post");

        $note = new \Zend_Form_Element_Note("note");
        $note->setValue($this->message);
        $this->addElement($note);

        if ($this->style === self::YES_NO) {
            $this->addElement('submit', 'btn_yes', array(
                'label' => 'Yes'
                )
            );
            $this->addElement('submit', 'btn_no', array(
                'label' => 'No'
                )
            );
        } elseif ($this->style === self::OK_CANCEL) {
            $this->addElement('submit', 'btn_ok', array(
                'label' => 'Ok'
                )
            );
            $this->addElement('submit', 'btn_cancel', array(
                'label' => 'Cancel'
                )
            );
        } else {
            throw new ProgrammingError("Button style must be one of: YES_NO, OK_CANCEL");
        }
    }

    public function isConfirmed()
    {
        if ($this->style === self::YES_NO) {
            return $this->isSubmitted("btn_yes");
        } elseif ($this->style === self::OK_CANCEL) {
            return $this->isSubmitted("btn_ok");
        }
    }
}

?>
