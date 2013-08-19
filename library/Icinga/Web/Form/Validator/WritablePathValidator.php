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

namespace Icinga\Web\Form\Validator;

use \Zend_Validate_Abstract;

/**
 * Validator that interprets the value as a path and checks if it's writable
 *
 */
class WritablePathValidator extends Zend_Validate_Abstract
{
    /**
     * The messages to write on differen error states
     *
     * @var array
     * @see Zend_Validate_Abstract::$_messageTemplates‚
     */
    // @codingStandardsIgnoreStart
    protected $_messageTemplates = array(
        'NOT_WRITABLE'  =>  'Path is not writable'
    );
    // @codingStandardsIgnoreEnd

    /**
     * Check whether the given value is writable path
     *
     * @param string $value     The value submitted in the form
     * @param null $context     The context of the form
     *
     * @return bool             True when validation worked, otherwise false‚
     * @see Zend_Validate_Abstract::isValid()‚
     */
    public function isValid($value, $context = null)
    {
        $value = (string) $value;
        $this->_setValue($value);

        if ((file_exists($value) && is_writable($value)) ||
            (is_dir(dirname($value)) != '' && is_writable(dirname($value)))) {
            return true;
        }
        $this->_error('NOT_WRITABLE');
        return false;
    }
}
