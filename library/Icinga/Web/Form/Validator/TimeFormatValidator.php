<?php
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

namespace Icinga\Web\Form\Validator;

use \Zend_Validate_Abstract;

/**
 * Validator that checks if a textfield contains a correct time format
 */
class TimeFormatValidator extends Zend_Validate_Abstract
{

    /**
     * Valid time characters according to @see http://www.php.net/manual/en/function.date.php
     *
     * @var array
     * @see http://www.php.net/manual/en/function.date.php
     */
    private $validChars = array('a', 'A', 'B', 'g', 'G', 'h', 'H', 'i', 's', 'u');

    /**
     * List of sensible time separators
     *
     * @var array
     */
    private $validSeparators = array(' ', ':', '-', '/', ';', ',', '.');

    /**
     * Error templates
     *
     * @var array
     * @see Zend_Validate_Abstract::$_messageTemplates
     */
    // @codingStandardsIgnoreStart
    protected $_messageTemplates = array(
        'INVALID_CHARACTERS' => 'Invalid time format'
    );
    // @codingStandardsIgnoreEnd

    /**
     * Validate the input value
     *
     * @param   string    $value    The format string to validate
     * @param   null      $context  The form context (ignored)
     *
     * @return  bool True when the input is valid, otherwise false
     *
     * @see     Zend_Validate_Abstract::isValid()
     */
    public function isValid($value, $context = null)
    {
        $rest = trim($value, join(' ', array_merge($this->validChars, $this->validSeparators)));
        if (strlen($rest) > 0) {
            $this->_error('INVALID_CHARACTERS');
            return false;
        }
        return true;
    }
}
