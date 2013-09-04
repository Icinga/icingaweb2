<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;

/**
 *  Decorator that styles forms in the DOM Bootstrap wants for it's forms
 *
 *  This component replaces the dt/dd wrapping of elements with the approach used by bootstrap.
 *
 *  Labels are drawn for all elements, except hidden, button and submit elements. If you want a
 *  placeholder for this elements, set the 'addLabelPlaceholder' property. This can be useful in
 *  cases where you want to put inputs with and inputs without labels on the same line and don't
 *  want buttons to 'jump'
 */
class BootstrapForm extends Zend_Form_Decorator_Abstract
{
    /**
     * An array of elements that won't get a <label> dom added per default
     *
     * @var array
     */
    private static $noLabel = array(
        'Zend_Form_Element_Hidden',
        'Zend_Form_Element_Button',
        'Zend_Form_Element_Submit'
    );

    /**
     * Return the DOM for the element label
     *
     * @param   String $elementName     The name of the element
     *
     * @return  String                  The DOM for the form element's label
     */
    public function getLabel($elementName)
    {
        $label = $this->getElement()->getLabel();
        if (!$label) {
            $label = '&nbsp;';
        }
        if (in_array($this->getElement()->getType(), self::$noLabel)
            && !$this->getElement()->getAttrib('addLabelPlaceholder', false)) {
            $label = '';
        } else {
            if (in_array($this->getElement()->getType(), self::$noLabel)) {
                $label = '&nbsp;';
            }
            $label = '<label for="' . $elementName . '">' . $label . '</label>';
        }
        return $label;
    }


    /**
     * Render this element
     *
     * Renders as the following:
     * <div class="form-group">
     *      $dtLabel
     *      $dtElement
     * </div>
     *
     * @param  String $content      The content of the form element
     *
     * @return String               The decorated form element
     */
    public function render($content)
    {
        $el = $this->getElement();
        $elementName = $el->getName();
        $label = $this->getLabel($elementName);
        return '<div class="form-group" id="' . $elementName . '-element">'
                    . $label
                    . $content
               . '</div>';
    }
}
