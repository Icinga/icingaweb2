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

class TriStateValidator extends Zend_Validate_Abstract {

    /**
     * @var null
     */
    private $validPattern = null;

    /**
     * Validate the input value and set the value of @see validPattern if the input machtes
     * a state description like '0', '1' or 'unchanged'
     *
     * @param   string  $value      The value to validate
     * @param   null    $context    The form context (ignored)
     *
     * @return  bool True when the input is valid, otherwise false
     *
     * @see     Zend_Validate_Abstract::isValid()
     */
    public function isValid($value, $context = null)
    {
        if (!is_string($value) && !is_int($value)) {
            $this->error('INVALID_TYPE');
            return false;
        }

        if (is_string($value)) {
            $value = intval($value);
            if ($value === 'unchanged') {
                $this->validPattern = null;
                return true;
            }
        }

        if (is_int($value)) {
            if ($value === 1 || $value === 0) {
                $this->validPattern = $value;
                return true;
            }
        }
        return false;
    }

    public function getValidPattern()
    {
        return $this->validPattern;
    }
}