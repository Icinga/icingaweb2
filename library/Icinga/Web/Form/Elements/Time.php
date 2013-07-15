<?php
namespace Icinga\Form\Elements;

/**
 * Time form element
 *
 * @TODO: The given label for this element is not displayed. (Reason unknown)
 */
class Time extends \Zend_Form_Element_Xhtml
{
    /**
     * Default form view helper to use for rendering
     * @var string
     */
    public $helper = "formTime";
}

?>
