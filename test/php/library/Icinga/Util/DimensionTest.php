<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Util;

use Icinga\Util\Dimension;
use Icinga\Test\BaseTestCase;

class DimensionTest extends BaseTestCase
{
    public function testStringFactoryWithValidInput()
    {
        $d = Dimension::fromString("200px");
        $this->assertEquals(200, $d->getValue(), "Asserting the numeric value of px input to be correctly parsed");
        $this->assertEquals(Dimension::UNIT_PX, $d->getUnit(), "Asserting the unit of px input to be correctly parsed");

        $d = Dimension::fromString("40%");
        $this->assertEquals(40, $d->getValue(), "Asserting the numeric value of % input to be correctly parsed");
        $this->assertEquals(
            Dimension::UNIT_PERCENT,
            $d->getUnit(),
            "Asserting the unit of % input to be correctly parsed"
        );

        $d = Dimension::fromString("4044em");
        $this->assertEquals(4044, $d->getValue(), "Asserting the numeric value of em input to be correctly parsed");
        $this->assertEquals(Dimension::UNIT_EM, $d->getUnit(), "Asserting the unit of em input to be correctly parsed");

        $d = Dimension::fromString("010pt");
        $this->assertEquals(10, $d->getValue(), "Asserting the numeric value of pt input to be correctly parsed");
        $this->assertEquals(Dimension::UNIT_PT, $d->getUnit(), "Asserting the unit of pt input to be correctly parsed");
    }

    public function testStringCreation()
    {
        $d = new Dimension(1000, Dimension::UNIT_PX);
        $this->assertEquals("1000px", (string) $d, "Asserting value-unit string creation to be correct");

        $d = new Dimension(40.5, Dimension::UNIT_PT);
        $this->assertEquals("40pt", (string) $d, "Asserting float values being truncated by now");
    }

    public function testInvalidDimensions()
    {
        $d = new Dimension(-20, Dimension::UNIT_PX);
        $this->assertFalse($d->isDefined(), "Asserting a negative dimension to be considered invalid");
    }
}
