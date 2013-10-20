<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @author Icinga Development Team <info@icinga.org>
 */
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

        // Do we have a value?
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
        $xhtml = '<div class="datetime">'
            . '<input type="text" name="' . $name . '"'
            . ' id="' . $name . '"'
            . $value
            . $disabled
            . $this->_htmlAttribs($attribs);

        if ($jspicker === true) {
            $xhtml .= 'data-icinga-component="app/datetime"';
        }

        $xhtml .= $this->getClosingBracket()
            . '</div>';

        return $xhtml;
    }
}

// @codingStandardsIgnoreStop
