<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \DateTime;
use \DateTimeZone;
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
     * Retrieve current request
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
     * Format date according to current user's format
     *
     * @param   int     $timestamp  A unix timestamp
     * @return  string  The formatted date string
     */
    public function formatDate($timestamp)
    {
        $dt = new DateTime($timestamp, $this->getTimeZone());
        return $dt->format($this->getDateFormat());
    }

    /**
     * Format time according to current user's format
     *
     * @param   int     $timestamp  A unix timestamp
     * @return  string  The formatted time string
     */
    public function formatTime($timestamp)
    {
        $dt = new DateTime($timestamp, $this->getTimeZone());
        return $dt->format($this->getTimeFormat());
    }

    /**
     * Format datetime according to current user's format
     *
     * @param   int     $timestamp  A unix timestamp
     * @return  string  The formatted datetime string
     */
    public function formatDateTime($timestamp)
    {
        $dt = new DateTime($timestamp, $this->getTimeZone());
        return $dt->format($this->getDateTimeFormat());
    }

    /**
     * Retrieve the current user's timezone
     *
     * @return  DateTimeZone
     */
    private function getTimeZone()
    {
        return new DateTimeZone($this->request->getUser()->getTimeZone());
    }

    /**
     * Retrieve the current user's date format string
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
     * Retrieve the current user's time format string
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
     * Retrieve the current user's datetime format string
     *
     * @return  string
     */
    public function getDateTimeFormat()
    {
        return $this->getDateFormat() . ' ' . $this->getTimeFormat();
    }
}

// @codingStandardsIgnoreStop
