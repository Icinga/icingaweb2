<?php
namespace Icinga\Form\Elements;

/**
 * Number form element
 *
 * @TODO: The given label for this element is not displayed. (Reason unknown)
 */
class Number extends \Zend_Form_Element_Xhtml
{
    /**
     * Default form view helper to use for rendering
     * @var string
     */
    public $helper = "formNumber";
}

?>
