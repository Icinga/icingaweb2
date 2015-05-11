<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Application\Icinga;
use Icinga\Util\DateTimeFactory;

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
    protected $request;

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
     * @return  $this
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
     *
     * @return  string
     */
    public function format($timestamp, $format)
    {
        $dt = DateTimeFactory::create();
        if (DateTimeFactory::isUnixTimestamp($timestamp)) {
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
     *
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
     *
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
        // TODO(mh): Missing localized format (#6077)
        return 'd/m/Y';
    }

    /**
     * Retrieve the user's time format string
     *
     * @return  string
     */
    public function getTimeFormat()
    {
        // TODO(mh): Missing localized format (#6077)
        return 'g:i A';
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
