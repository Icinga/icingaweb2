<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Module\Monitoring\Plugin;

use Icinga\Test\BaseTestCase;
use Icinga\Module\Monitoring\Plugin\ThresholdRange;

class ThresholdRangeTest extends BaseTestCase
{
    public function testFromStringProperlyParsesDoubleExclusiveRanges()
    {
        $outside0And10 = ThresholdRange::fromString('10');
        $this->assertEquals(
            0,
            $outside0And10->getMin(),
            'ThresholdRange::fromString() does not identify zero as default minimum for double exclusive ranges'
        );
        $this->assertEquals(
            10,
            $outside0And10->getMax(),
            'ThresholdRange::fromString() does not identify ten as explicit maximum for double exclusive ranges'
        );
        $this->assertFalse(
            $outside0And10->isInverted(),
            'ThresholdRange::fromString() identifies double exclusive ranges as inclusive'
        );

        $outside10And20 = ThresholdRange::fromString('10:20');
        $this->assertEquals(
            10,
            $outside10And20->getMin(),
            'ThresholdRange::fromString() does not identify ten as explicit minimum for double exclusive ranges'
        );
        $this->assertEquals(
            20,
            $outside10And20->getMax(),
            'ThresholdRange::fromString() does not identify twenty as explicit maximum for double exclusive ranges'
        );
        $this->assertFalse(
            $outside10And20->isInverted(),
            'ThresholdRange::fromString() identifies double exclusive ranges as inclusive'
        );
    }

    /**
     * @depends testFromStringProperlyParsesDoubleExclusiveRanges
     */
    public function testContainsCorrectlyEvaluatesDoubleExclusiveRanges()
    {
        $outside0And10 = ThresholdRange::fromString('10');
        $this->assertFalse(
            $outside0And10->contains(-1),
            'ThresholdRange::contains() identifies negative values as greater than or equal to zero'
        );
        $this->assertFalse(
            $outside0And10->contains(11),
            'ThresholdRange::contains() identifies eleven as smaller than or equal to ten'
        );
        $this->assertTrue(
            $outside0And10->contains(10),
            'ThresholdRange::contains() identifies 10 as outside the range 0..10'
        );

        $outside10And20 = ThresholdRange::fromString('10:20');
        $this->assertFalse(
            $outside10And20->contains(9),
            'ThresholdRange::contains() identifies nine as greater than or equal to 10'
        );
        $this->assertFalse(
            $outside10And20->contains(21),
            'ThresholdRange::contains() identifies twenty-one as smaller than or equal to twenty'
        );
        $this->assertTrue(
            $outside10And20->contains(20),
            'ThresholdRange::contains() identifies 20 as outside the range 10..20'
        );
    }

    public function testFromStringProperlyParsesSingleExclusiveRanges()
    {
        $smallerThan10 = ThresholdRange::fromString('10:');
        $this->assertEquals(
            10,
            $smallerThan10->getMin(),
            'ThresholdRange::fromString() does not identify ten as explicit minimum for single exclusive ranges'
        );
        $this->assertNull(
            $smallerThan10->getMax(),
            'ThresholdRange::fromString() does not identify infinity as default maximum for single exclusive ranges'
        );
        $this->assertFalse(
            $smallerThan10->isInverted(),
            'ThresholdRange::fromString() identifies single exclusive ranges as inclusive'
        );

        $greaterThan10 = ThresholdRange::fromString('~:10');
        $this->assertNull(
            $greaterThan10->getMin(),
            'ThresholdRange::fromString() does not identify infinity as explicit minimum for single exclusive ranges'
        );
        $this->assertEquals(
            10,
            $greaterThan10->getMax(),
            'ThresholdRange::fromString() does not identify ten as explicit maximum for single exclusive ranges'
        );
        $this->assertFalse(
            $greaterThan10->isInverted(),
            'ThresholdRange::fromString() identifies single exclusive ranges as inclusive'
        );
    }

    /**
     * @depends testFromStringProperlyParsesSingleExclusiveRanges
     */
    public function testContainsCorrectlyEvaluatesSingleExclusiveRanges()
    {
        $smallerThan10 = ThresholdRange::fromString('10:');
        $this->assertFalse(
            $smallerThan10->contains(9),
            'ThresholdRange::contains() identifies nine as greater than or equal to ten'
        );
        $this->assertTrue(
            $smallerThan10->contains(PHP_INT_MAX),
            'ThresholdRange::contains() identifies infinity as outside the range 10..~'
        );

        $greaterThan10 = ThresholdRange::fromString('~:10');
        $this->assertFalse(
            $greaterThan10->contains(11),
            'ThresholdRange::contains() identifies eleven as smaller than or equal to ten'
        );
        $this->assertTrue(
            $greaterThan10->contains(~PHP_INT_MAX),
            'ThresholdRange::contains() identifies negative infinity as outside the range ~..10'
        );
    }

    public function testFromStringProperlyParsesInclusiveRanges()
    {
        $inside0And10 = ThresholdRange::fromString('@10');
        $this->assertEquals(
            0,
            $inside0And10->getMin(),
            'ThresholdRange::fromString() does not identify zero as default minimum for inclusive ranges'
        );
        $this->assertEquals(
            10,
            $inside0And10->getMax(),
            'ThresholdRange::fromString() does not identify ten as explicit maximum for inclusive ranges'
        );
        $this->assertTrue(
            $inside0And10->isInverted(),
            'ThresholdRange::fromString() identifies inclusive ranges as double exclusive'
        );

        $inside10And20 = ThresholdRange::fromString('@10:20');
        $this->assertEquals(
            10,
            $inside10And20->getMin(),
            'ThresholdRange::fromString() does not identify ten as explicit minimum for inclusive ranges'
        );
        $this->assertEquals(
            20,
            $inside10And20->getMax(),
            'ThresholdRange::fromString() does not identify twenty as explicit maximum for inclusive ranges'
        );
        $this->assertTrue(
            $inside10And20->isInverted(),
            'ThresholdRange::fromString() identifies inclusive ranges as double exclusive'
        );

        $greaterThan10 = ThresholdRange::fromString('@10:');
        $this->assertEquals(
            10,
            $greaterThan10->getMin(),
            'ThresholdRange::fromString() does not identify ten as explicit minimum for inclusive ranges'
        );
        $this->assertNull(
            $greaterThan10->getMax(),
            'ThresholdRange::fromString() does not identify infinity as default maximum for inclusive ranges'
        );
        $this->assertTrue(
            $greaterThan10->isInverted(),
            'ThresholdRange::fromString() identifies inclusive ranges as single exclusive'
        );

        $smallerThan10 = ThresholdRange::fromString('@~:10');
        $this->assertNull(
            $smallerThan10->getMin(),
            'ThresholdRange::fromString() does not identify infinity as explicit minimum for inclusive ranges'
        );
        $this->assertEquals(
            10,
            $smallerThan10->getMax(),
            'ThresholdRange::fromString() does not identify ten as explicit maximum for inclusive ranges'
        );
        $this->assertTrue(
            $smallerThan10->isInverted(),
            'ThresholdRange::fromString() identifies inclusive ranges as single exclusive'
        );
    }

    /**
     * @depends testFromStringProperlyParsesInclusiveRanges
     */
    public function testContainsCorrectlyEvaluatesInclusiveRanges()
    {
        $inside0And10 = ThresholdRange::fromString('@10');
        $this->assertFalse(
            $inside0And10->contains(10),
            'ThresholdRange::contains() identifies ten as greater than ten'
        );
        $this->assertTrue(
            $inside0And10->contains(11),
            'ThresholdRange::contains() identifies eleven as smaller than or equal to ten'
        );
        $this->assertTrue(
            $inside0And10->contains(-1),
            'ThresholdRange::contains() identifies negative values as greater than or equal to zero'
        );

        $inside10And20 = ThresholdRange::fromString('@10:20');
        $this->assertFalse(
            $inside10And20->contains(20),
            'ThresholdRange::contains() identifies twenty as greater than twenty'
        );
        $this->assertTrue(
            $inside10And20->contains(21),
            'ThresholdRange::contains() identifies twenty-one as smaller than or equal to twenty'
        );
        $this->assertTrue(
            $inside10And20->contains(9),
            'ThresholdRange::contains() identifies nine as greater than or equal to ten'
        );

        $greaterThan10 = ThresholdRange::fromString('@10:');
        $this->assertFalse(
            $greaterThan10->contains(PHP_INT_MAX),
            'ThresholdRange::contains() identifies infinity as smaller than ten'
        );
        $this->assertTrue(
            $greaterThan10->contains(9),
            'ThresholdRange::contains() identifies nine as greater than or equal to ten'
        );

        $smallerThan10 = ThresholdRange::fromString('@~:10');
        $this->assertFalse(
            $smallerThan10->contains(~PHP_INT_MAX),
            'ThresholdRange::contains() identifies negative infinity as greater than ten'
        );
        $this->assertTrue(
            $smallerThan10->contains(11),
            'ThresholdRange::contains() identifies eleven as smaller than or equal to ten'
        );
    }

    public function testFromStringProperlyParsesEmptyThresholds()
    {
        $emptyThreshold = ThresholdRange::fromString('');
        $this->assertNull(
            $emptyThreshold->getMin(),
            'ThresholdRange::fromString() does not identify negative infinity as implicit minimum for empty strings'
        );
        $this->assertNull(
            $emptyThreshold->getMax(),
            'ThresholdRange::fromString() does not identify infinity as implicit maximum for empty strings'
        );
        $this->assertFalse(
            $emptyThreshold->isInverted(),
            'ThresholdRange::fromString() identifies empty strings as inclusive ranges rather than exclusive'
        );
    }

    /**
     * @depends testFromStringProperlyParsesEmptyThresholds
     */
    public function testContainsEvaluatesEverythingToTrueForEmptyThresholds()
    {
        $emptyThreshold = ThresholdRange::fromString('');
        $this->assertTrue(
            $emptyThreshold->contains(0),
            'ThresholdRange::contains() does not identify zero as valid without any threshold'
        );
        $this->assertTrue(
            $emptyThreshold->contains(10),
            'ThresholdRange::contains() does not identify ten as valid without any threshold'
        );
        $this->assertTrue(
            $emptyThreshold->contains(PHP_INT_MAX),
            'ThresholdRange::contains() does not identify infinity as valid without any threshold'
        );
        $this->assertTrue(
            $emptyThreshold->contains(~PHP_INT_MAX),
            'ThresholdRange::contains() does not identify negative infinity as valid without any threshold'
        );
    }

    public function testInvalidThresholdNotationsAreRenderedAsIs()
    {
        $this->assertEquals(
            ':',
            (string) ThresholdRange::fromString(':')
        );
        $this->assertEquals(
            '~:',
            (string) ThresholdRange::fromString('~:')
        );
        $this->assertEquals(
            '20:10',
            (string) ThresholdRange::fromString('20:10')
        );
        $this->assertEquals(
            '10@',
            (string) ThresholdRange::fromString('10@')
        );
        $this->assertEquals(
            'foo',
            (string) ThresholdRange::fromString('foo')
        );
    }
}
