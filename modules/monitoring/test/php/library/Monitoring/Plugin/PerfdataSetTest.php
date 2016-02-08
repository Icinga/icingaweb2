<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Module\Monitoring\Plugin;

use Icinga\Test\BaseTestCase;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;

class PerfdataSetWithPublicData extends PerfdataSet
{
    public $perfdata = array();
}

class PerfdataSetTest extends BaseTestCase
{
    public function testWhetherValidSimplePerfdataLabelsAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('key1=val1   key2=val2 key3  =val3');
        $this->assertEquals(
            'key1',
            $pset->perfdata[0]->getLabel(),
            'PerfdataSet does not correctly parse valid simple labels'
        );
        $this->assertEquals(
            'key2',
            $pset->perfdata[1]->getLabel(),
            'PerfdataSet does not correctly parse valid simple labels'
        );
        $this->assertEquals(
            'key3',
            $pset->perfdata[2]->getLabel(),
            'PerfdataSet does not correctly parse valid simple labels'
        );
    }

    public function testWhetherNonQuotedPerfdataLablesWithSpacesAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('key 1=val1 key 1 + 1=val2');
        $this->assertEquals(
            'key 1',
            $pset->perfdata[0]->getLabel(),
            'PerfdataSet does not correctly parse non quoted labels with spaces'
        );
        $this->assertEquals(
            'key 1 + 1',
            $pset->perfdata[1]->getLabel(),
            'PerfdataSet does not correctly parse non quoted labels with spaces'
        );
    }

    public function testWhetherValidQuotedPerfdataLabelsAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('\'key 1\'=val1 "key 2"=val2');
        $this->assertEquals(
            'key 1',
            $pset->perfdata[0]->getLabel(),
            'PerfdataSet does not correctly parse valid quoted labels'
        );
        $this->assertEquals(
            'key 2',
            $pset->perfdata[1]->getLabel(),
            'PerfdataSet does not correctly parse valid quoted labels'
        );
    }

    public function testWhetherInvalidQuotedPerfdataLabelsAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('\'key 1=val1 key 2"=val2');
        $this->assertEquals(
            'key 1',
            $pset->perfdata[0]->getLabel(),
            'PerfdataSet does not correctly parse invalid quoted labels'
        );
        $this->assertEquals(
            'key 2"',
            $pset->perfdata[1]->getLabel(),
            'PerfdataSet does not correctly parse invalid quoted labels'
        );
    }

    /**
     * @depends testWhetherValidSimplePerfdataLabelsAreProperlyParsed
     */
    public function testWhetherAPerfdataSetIsIterable()
    {
        $pset = PerfdataSet::fromString('key=value');
        foreach ($pset as $p) {
            $this->assertEquals('key', $p->getLabel());
            return;
        }

        $this->fail('PerfdataSet objects cannot be iterated');
    }

    public function testWhetherPerfdataSetsCanBeInitializedWithEmptyStrings()
    {
        $pset = PerfdataSetWithPublicData::fromString('');
        $this->assertEmpty($pset->perfdata, 'PerfdataSet::fromString does not accept emtpy strings');
    }
}
