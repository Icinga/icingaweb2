<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Render date input controls
 */
class Zend_View_Helper_FormDate extends Zend_View_Helper_FormElement
{
    /**
     * Render the date input control
     *
     * @param   string  $name
     * @param   int     $value
     * @param   array   $attribs
     *
     * @return  string  The rendered date input control
     */
    public function formDate($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);

        extract($info);  // name, id, value, attribs, options, listsep, disable
        /** @var string $id  */
        /** @var bool $disable  */

        $disabled = '';
        if ($disable) {
            $disabled = ' disabled="disabled"';
        }

        /** @var \Icinga\Web\View $view */
        $view = $this->view;

        $html5 = sprintf(
            '<input type="date" name="%s" id="%s" value="%s"%s%s%s',
            $view->escape($name),
            $view->escape($id),
            $view->escape($value),
            $disabled,
            $this->_htmlAttribs($attribs),
            $this->getClosingBracket()
        );

        return $html5;
    }
}
