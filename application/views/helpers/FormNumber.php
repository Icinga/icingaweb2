<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Helper to generate a number input
 */
class Zend_View_Helper_FormNumber extends \Zend_View_Helper_FormText
{
    /**
     * Generates a html number input
     *
     * @access public
     *
     * @param string $name The element name.
     * @param string $value The default value.
     * @param array $attribs Attributes which should be added to the input tag.
     *
     * @return string The input tag and options XHTML.
     */
    public function formNumber($name, $value = null, $attribs = null)
    {
        return '<input type="number"'
             . ' name="' . $this->view->escape($name) . '"'
             . ' value="' . $this->view->escape($value) . '"'
             . ' id="' . $this->view->escape($name) . '"'
             . $this->_htmlAttribs($attribs)
             . $this->getClosingBracket();
    }
}
