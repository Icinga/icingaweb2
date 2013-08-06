<?php

use \DateTime;
use \DateTimeZone;
use Icinga\Application\Icinga;
use Icinga\Application\Config as IcingaConfig;

class Zend_View_Helper_DateFormat extends Zend_View_Helper_Abstract
{
    public function dateFormat()
    {
        return $this;
    }

    public function formatDate($timestamp)
    {
        $dt = new DateTime($timestamp, $this->getTimeZone());
        return $dt->format($this->getDateFormat());
    }

    public function timeFormat($timestamp)
    {
        $dt = new DateTime($timestamp, $this->getTimeZone());
        return $dt->format($this->getTimeFormat());
    }

    public function formatDateTime($timestamp)
    {
        $dt = new DateTime($timestamp, $this->getTimeZone());
        return $dt->format($this->getDateTimeFormat());
    }

    private function getRequest()
    {
        // TODO(el/WIP): Set via constructor
        return Icinga::app()->getFrontController()->getRequest();
    }

    private function getTimeZone()
    {
        return new DateTimeZone($this->getRequest()->getUser()->getTimeZone());
    }

    public function getDateFormat()
    {
        return $this->getRequest()->getUser()->getPreferences()->get(
            'dateFormat', IcingaConfig::app()->global->get('dateFormat', 'Y-m-d')
        );
    }

    public function getTimeFormat()
    {
        return $this->getRequest()->getUser()->getPreferences()->get(
            'timeFormat', IcingaConfig::app()->global->get('timeFormat', 'H:i:s')
        );
    }

    public function getDateTimeFormat()
    {
        return $this->getDateFormat() . ' ' . $this->getTimeFormat();
    }
}
