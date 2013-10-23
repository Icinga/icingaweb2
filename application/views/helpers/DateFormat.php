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

use \Icinga\Application\Icinga;
use \Icinga\Application\Config;
use \Icinga\Util\DateTimeFactory;
use \Icinga\Web\Form\Validator\DateTimeValidator;

/**
 * Helper to format date and time. Utilizes DateTimeFactory to ensure time zone awareness
 *
 * @see DateTimeFactory::create()
 */
class Zend_View_Helper_DateFormat extends Zend_View_Helper_Abstract
{
    /**
     * Current request
     *
     * @var Zend_Controller_Request_Abstract
     */
    private $request;

    /**
     * Constructor
     *
     * Retrieves the request from the application's front controller if not given.
     *
     * @param Zend_Controller_Request_Abstract  $request    The request to use
     */
    public function __construct(Zend_Controller_Request_Abstract $request = null)
    {
        if ($request === null) {
            $this->request = Icinga::app()->getFrontController()->getRequest();
        } else {
            $this->request = $request;
        }
    }

    /**
     * Helper entry point
     *
     * @return  self
     */
    public function dateFormat()
    {
        return $this;
    }

    /**
     * Return date formatted according to given format respecting the user's timezone
     *
     * @param   int     $timestamp
     * @param   string  $format
     * @return  string
     */
    public function format($timestamp, $format)
    {
        $dt = DateTimeFactory::create();
        if (DateTimeValidator::isUnixTimestamp($timestamp)) {
            $dt->setTimestamp($timestamp);
        } else {
            return $timestamp;
        }
        return $dt->format($format);
    }

    /**
     * Format date according to user's format
     *
     * @param   int     $timestamp  A unix timestamp
     * @return  string  The formatted date string
     */
    public function formatDate($timestamp)
    {
        return $this->format($timestamp, $this->getDateFormat());
    }

    /**
     * Format time according to user's format
     *
     * @param   int     $timestamp  A unix timestamp
     * @return  string  The formatted time string
     */
    public function formatTime($timestamp)
    {
        return $this->format($timestamp, $this->getTimeFormat());
    }

    /**
     * Format datetime according to user's format
     *
     * @param   int     $timestamp  A unix timestamp
     * @return  string  The formatted datetime string
     */
    public function formatDateTime($timestamp)
    {
        return $this->format($timestamp, $this->getDateTimeFormat());
    }

    /**
     * Retrieve the user's date format string
     *
     * @return  string
     */
    public function getDateFormat()
    {
        return $this->request->getUser()->getPreferences()->get(
            'app.dateFormat', Config::app()->global->get('dateFormat', 'd/m/Y')
        );
    }

    /**
     * Retrieve the user's time format string
     *
     * @return  string
     */
    public function getTimeFormat()
    {
        return $this->request->getUser()->getPreferences()->get(
            'app.timeFormat', Config::app()->global->get('timeFormat', 'g:i A')
        );
    }

    /**
     * Retrieve the user's datetime format string
     *
     * @return  string
     */
    public function getDateTimeFormat()
    {
        return $this->getDateFormat() . ' ' . $this->getTimeFormat();
    }
}

// @codingStandardsIgnoreStop
