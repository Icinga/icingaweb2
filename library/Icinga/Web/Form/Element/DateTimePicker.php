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

namespace Icinga\Web\Form\Element;

use \Zend_Form_Element_Xhtml;
use \Icinga\Util\DateTimeFactory;
use \Icinga\Exception\ProgrammingError;

/**
 * Datetime form element which returns the input as Unix timestamp after the input has been proven valid. Utilizes
 * DateTimeFactory to ensure time zone awareness
 *
 * @see isValid()
 */
class DateTimePicker extends Zend_Form_Element_Xhtml
{
    /**
     * View helper to use
     * @var string
     */
    public $helper = 'formDateTime';

    /**
     * Valid formats to check user input against
     * @var array
     */
    public $patterns;

    /**
     * Check whether a variable is a Unix timestamp
     *
     * @param   mixed   $timestamp
     * @return  bool
     */
    public function isUnixTimestamp($timestamp)
    {
        return (is_int($timestamp) || ctype_digit($timestamp))
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
     * Validate filtered date/time strings
     *
     * Expects one or more valid formats being set in $this->patterns. Sets element value as Unix timestamp
     * if the input is considered valid. Utilizes DateTimeFactory to ensure time zone awareness.
     *
     * @param   string  $value
     * @param   mixed   $context
     * @return  bool
     */
    public function isValid($value, $context = null)
    {
        if (!parent::isValid($value, $context)) {
            return false;
        }

        if (!is_string($value) && !is_int($value)) {
            $this->addErrorMessage(
                _('Invalid type given. Date/time string or Unix timestamp expected')
            );
            return false;
        }

        if ($this->isUnixTimestamp($value)) {
            $dt = DateTimeFactory::create();
            $dt->setTimestamp($value);
        } else {
            if (!isset($this->patterns)) {
                throw new ProgrammingError('Cannot parse datetime string without any pattern');
            }

            $match_found = false;
            foreach ($this->patterns as $pattern) {
                $dt = DateTimeFactory::parse($value, $pattern);
                if ($dt !== false && $dt->format($pattern) === $value) {
                    $match_found = true;
                    break;
                }
            }
            if (!$match_found) {
                return false;
            }
        }

        $this->setValue($dt->getTimestamp());

        return true;
    }
}
