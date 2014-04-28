<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

use \Zend_View_Helper_FormElement;

/**
 * Helper to generate a "datetime" element
 */
class Zend_View_Helper_FormTriStateCheckbox extends Zend_View_Helper_FormElement
{
    /**
     * Generate a tri-state checkbox
     *
     * @param   string  $name       The element name
     * @param   int     $value      The checkbox value
     * @param   array   $attribs    Attributes for the element tag
     *
     * @return  string  The element XHTML
     */
    public function formTriStateCheckbox($name, $value = null, $attribs = null)
    {
        $class = "";
        $xhtml = '<div class="tristate">'
                    . '<div>' . ($value == 1 ? ' ' : ($value === 'unchanged' ? ' ' : ' ' )) . '</div>'

                    . '<input class="' . $class . '" type="radio" value=1 name="'
                        . $name . '" ' . ($value == 1 ? 'checked' : '') . ' ">On</input> '

                    . '<input class="' . $class . '" type="radio" value=0 name="'
                        . $name . '" ' . ($value == 0 ? 'checked' : '') . ' ">Off</input> ';

        if ($value === 'unchanged') {
            $xhtml = $xhtml . '<input class="' . $class . '" type="radio" value="unchanged" name="'
            . $name . '" ' . 'checked "> Undefined </input>';
        };
        return $xhtml . '</div>';
    }
}
