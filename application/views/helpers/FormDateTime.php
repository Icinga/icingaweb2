<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

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
        if (isset($attribs['placeholder']) && $attribs['placeholder'] instanceof DateTime) {
            $attribs['placeholder'] = $this->formatDate($attribs['placeholder'], $attribs['local']);
        }
        $type = $attribs['local'] === true ? 'datetime-local' : 'datetime';
        unset($attribs['local']);  // Unset local to not render it again in $this->_htmlAttribs($attribs)
        $html5 =  sprintf(
            '<input type="%s" name="%s" id="%s" step="1" value="%s"%s%s%s',
            $type,
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
