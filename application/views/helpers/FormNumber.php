<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

/**
 * Render number input controls
 */
class Zend_View_Helper_FormNumber extends Zend_View_Helper_FormElement
{
    /**
     * Format a number
     *
     * @param   $number
     *
     * @return  string
     */
    public function formatNumber($number)
    {
        if (empty($number)) {
            return $number;
        }
        return $this->view->escape(
            sprintf(
                ctype_digit((string) $number) ? '%d' : '%F',
                $number
            )
        );
    }

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
        $min = '';
        if (isset($attribs['min'])) {
            $min = sprintf(' min="%s"', $this->formatNumber($attribs['min']));
        }
        unset($attribs['min']);  // Unset min to not render it again in $this->_htmlAttribs($attribs)
        $max = '';
        if (isset($attribs['max'])) {
            $max = sprintf(' max="%s"', $this->formatNumber($attribs['max']));
        }
        unset($attribs['max']);  // Unset max to not render it again in $this->_htmlAttribs($attribs)
        $step = '';
        if (isset($attribs['step'])) {
            $step = sprintf(' step="%s"', $attribs['step'] === 'any' ? 'any' : $this->formatNumber($attribs['step']));
        }
        unset($attribs['step']);  // Unset step to not render it again in $this->_htmlAttribs($attribs)
        $html5 = sprintf(
            '<input type="number" name="%s" id="%s" value="%s"%s%s%s%s%s%s',
            $this->view->escape($name),
            $this->view->escape($id),
            $this->view->escape($this->formatNumber($value)),
            $min,
            $max,
            $step,
            $disabled,
            $this->_htmlAttribs($attribs),
            $this->getClosingBracket()
        );
        return $html5;
    }
}
