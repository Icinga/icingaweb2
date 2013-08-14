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


/**
 * Decorator to hide elements using a &gt;noscript&lt; tag instead of
 * type='hidden' or css styles.
 *
 * This allows to hide depending elements for browsers with javascript
 * (who can then automatically refresh their pages) but show them in
 * case JavaScript is disabled
 */

namespace Icinga\Web\Form\Decorator;

use \Zend_Form_Decorator_Abstract;

class ConditionalHidden extends Zend_Form_Decorator_Abstract
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
    public function render($content ='')
    {
        $attributes = $this->getElement()->getAttribs();
        $condition = isset($attributes['condition']) ? $attributes['condition'] : 1;
        if ($condition != 1) {
            $content = '<noscript>' . $content . '</noscript>';
        }
        return $content;
    }
}
