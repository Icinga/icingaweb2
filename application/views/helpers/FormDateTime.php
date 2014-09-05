<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Zend_View_Helper_FormElement;

/**
 * Helper to generate a "datetime" element
 */
class Zend_View_Helper_FormDateTime extends Zend_View_Helper_FormElement
{
    /**
     * Generate a 'datetime' element
     *
     * @param   string  $name       The element name
     * @param   int     $value      The default timestamp
     * @param   array   $attribs    Attributes for the element tag
     *
     * @return  string  The element XHTML
     */
    public function formDateTime($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable
        // Is it disabled?
        $disabled = '';
        if ($disabled) {
            $disabled = ' disabled="disabled"';
        }

        $jspicker = (isset($attribs['jspicker']) && $attribs['jspicker'] === true) ? true : false;

        if (isset($value) && !empty($value)) {
            if ($jspicker) {
                $value = ' value="' . $this->view->dateFormat()->format($value, $attribs['defaultFormat']) . '"';
            } else {
                $value = ' value="' . $this->view->dateFormat()->formatDateTime($value) . '"';
            }
        } else {
            $value = '';
        }

        // Build the element
        $xhtml = '<div class="datetime' . (($jspicker === true) ? ' input-group' : ''). '">';

        $xhtml .= '<input type="text" name="' . $name . '"'
            . ' id="' . $name . '"'
            . $value
            . $disabled
            . $this->_htmlAttribs($attribs);

        if ($jspicker === true) {
            $xhtml .= 'data-icinga-component="app/datetime"';
        }

        $xhtml .= $this->getClosingBracket();

        if ($jspicker === true) {
            $xhtml .= '<span class="input-group-addon">'
                . '<a href="#">'
                . '<i class="icinga-icon-reschedule"></i>'
                . '</a>'
                . '</span>';
        }

        $xhtml .= '</div>';

        return $xhtml;
    }
}
