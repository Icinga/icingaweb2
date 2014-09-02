<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Zend_View_Helper_FormElement;

/**
 * Render number input controls
 */
class Zend_View_Helper_FormNumber extends Zend_View_Helper_FormElement
{
    /**
     * Render the number input control
     *
     * @param   string  $name
     * @param   int     $value
     * @param   array   $attribs
     *
     * @return  string  The rendered number input control
     */
    public function formNumber($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info);  // name, id, value, attribs, options, listsep, disable
        /** @var string $id  */
        /** @var bool $disable  */
        $disabled = '';
        if ($disable) {
            $disabled = ' disabled="disabled"';
        }
        $html5 = sprintf(
            '<input type="number" name="%s" id="%s" value="%s"%s%s%s',
            $this->view->escape($name),
            $this->view->escape($id),
            $this->view->escape($value),
            $disabled,
            $this->_htmlAttribs($attribs),
            $this->getClosingBracket()
        );
        return $html5;
    }
}
