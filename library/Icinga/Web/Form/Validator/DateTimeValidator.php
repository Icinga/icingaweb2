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

use \Icinga\Util\DateTimeFactory;
use \Zend_Validate_Abstract;
use \Icinga\Exception\ProgrammingError;

/**
 * Validator that checks if a textfield contains a correct date format
 */
class DateTimeValidator extends Zend_Validate_Abstract
{
    /**
     * Array of allowed patterns for datetime input
     *
     * @var array
     */
    private $patterns     = array();

    /**
     * If the input is not a timestamp this contains the pattern that
     * matched the input
     *
     * @var string|bool
     */
    private $validPattern = false;

    /**
     * Error templates
     *
     * @var array
     *
     * @see Zend_Validate_Abstract::$_messageTemplates
     */
    // @codingStandardsIgnoreStart
    protected $_messageTemplates = array();
    // @codingStandardsIgnoreEnd

    /**
     * Create this validator
     *
     * @param array $patterns   Array containing all allowed patterns as strings
     */
    public function __construct(array $patterns)
    {
        $this->patterns = $patterns;
        $this->_messageTemplates = array(
            'INVALID_TYPE'          => 'Invalid type given. Date/time string or Unix timestamp expected',
            'NO_MATCHING_PATTERN'   => 'Invalid format given, valid formats are ' . $this->getAllowedPatternList()
        );
    }

    /**
     * Check whether a variable is a Unix timestamp
     *
     * @param   mixed   $timestamp
     * @return  bool
     */
    public static function isUnixTimestamp($timestamp)
    {
        return (is_int($timestamp) || ctype_digit($timestamp))
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
     * Returns a printable string containing all configured patterns
     *
     * @return string
     */
    private function getAllowedPatternList()
    {
        return '"' . join('","', $this->patterns) . '"';
    }


    /**
     * Validate the input value and set the value of @see validPattern if the input machtes a pattern
     *
     * @param   string  $value      The format string to validate
     * @param   null    $context    The form context (ignored)
     *
     * @return  bool True when the input is valid, otherwise false
     *
     * @see     Zend_Validate_Abstract::isValid()
     */
    public function isValid($value, $context = null)
    {
        $this->validPattern = false;
        if (!is_string($value) && !is_int($value)) {
            $this->error('INVALID_TYPE');
            return false;
        }

        if ($this->isUnixTimestamp($value)) {
            $dt = DateTimeFactory::create();
            $dt->setTimestamp($value);
        } else {
            if (!isset($this->patterns)) {
                throw new ProgrammingError('There are no allowed timeformats configured');
            }

            $match_found = false;
            foreach ($this->patterns as $pattern) {
                $dt = DateTimeFactory::parse($value, $pattern);
                if ($dt !== false && $dt->format($pattern) === $value) {
                    $match_found = true;
                    $this->validPattern = $pattern;
                    break;
                }
            }
            if (!$match_found) {
                $this->_error('NO_MATCHING_PATTERN');
                return false;
            }
        }

        return true;
    }

    /**
     * Return the matched pattern if any or false if input is a timestamp
     *
     * @return bool|string      False if input was a timestamp otherwise string with the dateformat pattern
     */
    public function getValidPattern()
    {
        return $this->validPattern;
    }
}
