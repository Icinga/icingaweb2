<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \DateTime;
use Icinga\Application\Icinga;
use Icinga\Application\Config as IcingaConfig;

/**
 * Helper to format date and time
 */
class Zend_View_Helper_DateFormat extends Zend_View_Helper_Abstract
{

    /**
     * Current request
     * @var Zend_Controller_Request_Abstract
     */
    private $request;

    /**
     * Constructor
     *
     * Retrieve request
     */
    public function __construct()
    {
        $this->request = Icinga::app()->getFrontController()->getRequest();
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
    private function format($timestamp, $format)
    {
        // Using the Unix timestamp format to construct a new DateTime
        $dt = new DateTime('@' . $timestamp, $this->getTimeZone());
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
     * Retrieve the user's timezone
     *
     * @return  DateTimeZone
     */
    private function getTimeZone()
    {
        return $this->request->getUser()->getTimeZone();
    }

    /**
     * Retrieve the user's date format string
     *
     * @return  string
     */
    public function getDateFormat()
    {
        return $this->request->getUser()->getPreferences()->get(
            'dateFormat', IcingaConfig::app()->global->get('dateFormat', 'Y-m-d')
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
            'timeFormat', IcingaConfig::app()->global->get('timeFormat', 'H:i:s')
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
