<?php
namespace Icinga\Form\Elements;

class Note extends \Zend_Form_Element
{
    public $helper = "formNote";

    public function __construct($value)
    {
        parent::__construct("notification");
        $this->setValue("<p>$value</p>");
    }
}

?>