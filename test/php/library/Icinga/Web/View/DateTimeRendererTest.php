<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web;

use DateTime;
use Icinga\Test\BaseTestCase;
use Icinga\Web\View\DateTimeRenderer;

class DateTimeRendererTest extends BaseTestCase
{
    public function testWhetherCreateCreatesDateTimeRenderer()
    {
        $dateTime = new DateTime();
        $dt = DateTimeRenderer::create($dateTime);

        $this->assertInstanceOf(
            'Icinga\Web\View\DateTimeRenderer',
            $dt,
            'Dashboard::create() could not create DateTimeRenderer'
        );
    }

    /**
     * @depends testWhetherCreateCreatesDateTimeRenderer
     */
    public function testWhetherIsDateTimeReturnsRightType()
    {
        $dateTime = new DateTime('+1 day');
        $dt = DateTimeRenderer::create($dateTime);

        $this->assertTrue(
            $dt->isDateTime(),
            'Dashboard::isDateTime() returns wrong type'
        );
    }

    /**
     * @depends testWhetherCreateCreatesDateTimeRenderer
     */
    public function testWhetherIsTimeReturnsRightType()
    {
        $dateTime = new DateTime('+1 hour');
        $dt = DateTimeRenderer::create($dateTime);

        $this->assertTrue(
            $dt->isTime(),
            'Dashboard::isTime() returns wrong type'
        );
    }

    /**
     * @depends testWhetherCreateCreatesDateTimeRenderer
     */
    public function testWhetherIsTimespanReturnsRightType()
    {
        $dateTime = new DateTime('+1 minute');
        $dt = DateTimeRenderer::create($dateTime);

        $this->assertTrue(
            $dt->isTimespan(),
            'Dashboard::isTimespan() returns wrong type'
        );
    }

    /**
     * @depends testWhetherCreateCreatesDateTimeRenderer
     */
    public function testWhetherNormalizeReturnsNormalizedDateTime()
    {
        $dateTime = time();
        $dt = DateTimeRenderer::normalize($dateTime);

        $this->assertInstanceOf(
            'DateTime',
            $dt,
            'Dashboard::normalize() returns wrong instance'
        );
    }

    /**
     * @depends testWhetherCreateCreatesDateTimeRenderer
     */
    public function testWhetherRenderReturnsRightText()
    {
        $dateTime = new DateTime('+1 minute');
        $dt = DateTimeRenderer::create($dateTime);

        $text = $dt->render(
            '#1 The service is down since %s',
            '#2 The service is down since %s o\'clock.',
            '#3 The service is down for %s.'
        );

        $this->assertRegExp(
            '/#3/',
            $text,
            'Dashboard::render() returns wrong text'
        );
    }
}
