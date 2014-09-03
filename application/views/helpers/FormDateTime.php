<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Render date-and-time input controls
 */
class Zend_View_Helper_FormDateTime extends Zend_View_Helper_FormElement
{
    /**
     * Format date and time
     *
     * @param   DateTime  $dateTime
     * @param   bool      $local
     *
     * @return  string
     */
    public function formatDate(DateTime $dateTime, $local)
    {
        $format = (bool) $local === true ? 'Y-m-d\TH:i:s' : DateTime::RFC3339;
        return $dateTime->format($format);
    }

    /**
     * Render the date-and-time input control
     *
     * @param   string  $name       The element name
     * @param   DateTime $value      The default timestamp
     * @param   array   $attribs    Attributes for the element tag
     *
     * @return  string  The element XHTML
     */
    public function formDateTime($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info);  // name, id, value, attribs, options, listsep, disable
        /** @var string $id  */
        /** @var bool $disable  */
        $disabled = '';
        if ($disable) {
            $disabled = ' disabled="disabled"';
        }
        if ($value instanceof DateTime) {
            // If value was valid, it's a DateTime object
            $value = $this->formatDate($value, $attribs['local']);
        }
        $min = '';
        if (! empty($attribs['min'])) {
            $min = sprintf(' min="%s"', $this->formatDate($attribs['min'], $attribs['local']));
        }
        unset($attribs['min']);  // Unset min to not render it again in $this->_htmlAttribs($attribs)
        $max = '';
        if (! empty($attribs['max'])) {
            $max = sprintf(' max="%s"', $this->formatDate($attribs['max'], $attribs['local']));
        }
        unset($attribs['max']);  // Unset max to not render it again in $this->_htmlAttribs($attribs)
        $type = $attribs['local'] === true ? 'datetime-local' : 'datetime';
        unset($attribs['local']);  // Unset local to not render it again in $this->_htmlAttribs($attribs)
        $html5 =  sprintf(
            '<input type="%s" name="%s" id="%s" value="%s"%s%s%s%s%s',
            $type,
            $this->view->escape($name),
            $this->view->escape($id),
            $this->view->escape($value),
            $min,
            $max,
            $disabled,
            $this->_htmlAttribs($attribs),
            $this->getClosingBracket()
        );
        return $html5;
    }
}
