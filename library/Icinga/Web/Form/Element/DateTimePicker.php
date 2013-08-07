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

use \DateTime;
use \DateTimeZone;
use \Exception;
use Zend_Form_Element_Xhtml;
use Icinga\Application\Icinga;

/**
 * Datetime form element
 */
class DateTimePicker extends Zend_Form_Element_Xhtml
{
    /**
     * View helper to use
     * @var string
     */
    public $helper = 'formDateTime';

    /**
     * Time zone
     * @var mixed
     */
    private $timeZone;

    /**
     * Getter for the time zone
     *
     * If the time zone has never been set, the user's time zone is returned
     *
     * @return  DateTimeZone
     * @see     setTimeZone()
     */
    public function getTimeZone()
    {
        $timeZone = $this->timeZone;
        if ($timeZone === null) {
            $timeZone = Icinga::app()->getFrontController()->getRequest()->getUser()->getTimeZone();
        }
        return $timeZone;
    }

    /**
     * Setter for the time zone
     *
     * @param   DateTimeZone    $timeZone
     * @return  self
     */
    public function setTimeZone(DateTimeZone $timeZone)
    {
        $this->timeZone = $timeZone;
        return $this;
    }

    /**
     * Finds whether a variable is a Unix timestamp
     *
     * @param   mixed   $timestamp
     * @return  bool
     */
    public function isUnixTimestamp($timestamp)
    {
        return ((string) (int) $timestamp === (string) $timestamp)
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
     * Retrieve element value as unix timestamp respecting the user's timezone
     *
     * @param   mixed   $timeZone
     * @return  int
     */
    public function getValue()
    {
        $valueFiltered = parent::getValue();
        if ($this->isUnixTimestamp($valueFiltered)) {
            // Using the Unix timestamp format to construct a new DateTime
            $valueFiltered = '@' . $valueFiltered;
        }
        $dt = new DateTime($valueFiltered, $this->getTimeZone());
        return $dt->getTimestamp();
    }

    /**
     * Validate filtered date/time strings
     *
     * Expects formats that the php date parser understands
     *
     * @param   string  $value
     * @param   mixed   $context
     * @return  bool
     * @see     DateTime::__construct()
     */
    public function isValid($value, $context = null)
    {
        if (!parent::isValid(parent::getValue(), $context)) {
            return false;
        }

        if (!is_string($value) && !is_int($value)) {
            $this->addErrorMessage(
                _('Invalid type given. Date/time string or Unix timestamp expected')
            );
            return false;
        }

        if ($this->isUnixTimestamp($value)) {
            // Using the Unix timestamp format to construct a new DateTime
            $value = '@' . $value;
        }

        try {
            new DateTime($value);
        } catch (Exception $e) {
            $this->addErrorMessage(
                _(
                    'Failed to parse datetime string. See '
                        . 'http://www.php.net/manual/en/datetime.formats.php for valid formats'
                )
            );
            return false;
        }

        return true;
    }
}
