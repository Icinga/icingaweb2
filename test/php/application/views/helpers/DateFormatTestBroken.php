<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Views\Helper;

use Mockery;
use Zend_View_Helper_DateFormat;
use Icinga\Test\BaseTestCase;
use Icinga\Util\DateTimeFactory;

require_once BaseTestCase::$appDir . '/views/helpers/DateFormat.php';

class DateFormatTest extends BaseTestCase
{
    public function tearDown()
    {
        DateTimeFactory::setConfig(array('timezone' => date_default_timezone_get()));
    }

    public function testFormatReturnsCorrectDateWithTimezoneApplied()
    {
        DateTimeFactory::setConfig(array('timezone' => 'Europe/Berlin'));
        $helper = new Zend_View_Helper_DateFormat($this->getRequestMock());

        $this->assertEquals(
            '12:05',
            $helper->format(1397729100, 'H:i'),
            'Zend_View_Helper_DateFormat::format does not return a valid' .
            ' formatted time or does not apply the user\'s timezone'
        );
    }

    public function testFormatDateReturnsCorrectDate()
    {
        $helper = new Zend_View_Helper_DateFormat($this->getRequestMock('d_m_y'));

        $this->assertEquals(
            '17_04_14',
            $helper->formatDate(1397729100),
            'Zend_View_Helper_DateFormat::formatDate does not return a valid formatted date'
        );
    }

    public function testFormatTimeReturnsCorrectTime()
    {
        $helper = new Zend_View_Helper_DateFormat($this->getRequestMock(null, 'H:i'));

        $this->assertEquals(
            '10:05',
            $helper->formatTime(1397729100),
            'Zend_View_Helper_DateFormat::formatTime does not return a valid formatted time'
        );
    }

    public function testFormatDatetimeReturnsCorrectDatetime()
    {
        $helper = new Zend_View_Helper_DateFormat($this->getRequestMock('d m Y', 'H:i a'));

        $this->assertEquals(
            '17 04 2014 10:05 am',
            $helper->formatDateTime(1397729100),
            'Zend_View_Helper_DateFormat::formatDateTime does not return a valid formatted date and time'
        );
    }

    public function testGetDateFormatReturnsCorrectFormat()
    {
        $helper = new Zend_View_Helper_DateFormat($this->getRequestMock('d/m-y'));

        $this->assertEquals(
            'd/m-y',
            $helper->getDateFormat(),
            'Zend_View_Helper_DateFormat::getDateFormat does not return the user\'s date format'
        );
    }

    public function testGetTimeFormatReturnsCorrectFormat()
    {
        $helper = new Zend_View_Helper_DateFormat($this->getRequestMock(null, 'H.i A'));

        $this->assertEquals(
            'H.i A',
            $helper->getTimeFormat(),
            'Zend_View_Helper_DateFormat::getTimeFormat does not return the user\'s time format'
        );
    }

    public function testGetDatetimeFormatReturnsCorrectFormat()
    {
        $helper = new Zend_View_Helper_DateFormat($this->getRequestMock('d/m-y', 'H.i A'));

        $this->assertEquals(
            'd/m-y H.i A',
            $helper->getDateTimeFormat(),
            'Zend_View_Helper_DateFormat::getDateTimeFormat does not return the user\'s date and time format'
        );
    }

    public function testGetDateFormatReturnsFormatFromConfig()
    {
        $helper = new Zend_View_Helper_DateFormat($this->getRequestMock());

        $this->assertEquals(
            'd-m-y',
            $helper->getDateFormat(),
            'Zend_View_Helper_DateFormat::getDateFormat does not return the format set' .
            ' in the global configuration if the user\'s preferences do not provide one'
        );
    }

    public function testGetTimeFormatReturnsFormatFromConfig()
    {
        $helper = new Zend_View_Helper_DateFormat($this->getRequestMock());

        $this->assertEquals(
            'G:i a',
            $helper->getTimeFormat(),
            'Zend_View_Helper_DateFormat::getTimeFormat does not return the format set' .
            ' in the global configuration if the user\'s preferences do not provide one'
        );
    }

    public function testGetDatetimeFormatReturnsFormatFromConfig()
    {
        $helper = new Zend_View_Helper_DateFormat($this->getRequestMock());

        $this->assertEquals(
            'd-m-y G:i a',
            $helper->getDateTimeFormat(),
            'Zend_View_Helper_DateFormat::getDateTimeFormat does not return the format set' .
            ' in the global configuration if the user\'s preferences do not provide one'
        );
    }

    protected function getRequestMock($dateFormat = null, $timeFormat = null)
    {
        return Mockery::mock('\Zend_Controller_Request_Abstract');
    }
}
