<?php

/**
 * Helper to generate a text input with a timepicker being attached
 */
class Zend_View_Helper_FormTime extends \Zend_View_Helper_FormText
{
    /**
     * Generates a html time input
     *
     * @access public
     *
     * @param string $name The element name.
     * @param string $value The default value.
     * @param array $attribs Attributes which should be added to the input tag.
     *
     * @return string The input tag and options XHTML.
     */
    public function formTime($name, $value = null, $attribs = null)
    {
        return '<input type="time" class="timepick"'
             . ' name="' . $this->view->escape($name) . '"'
             . ' value="' . $this->view->escape($value) . '"'
             . ' id="' . $this->view->escape($name) . '"'
             . $this->_htmlAttribs($attribs)
             . $this->getClosingBracket();
    }
}

?>
