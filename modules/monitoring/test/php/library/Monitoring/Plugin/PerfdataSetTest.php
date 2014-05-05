<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
        $this->assertArrayHasKey(
            'key1',
            $pset->perfdata,
            'PerfdataSet does not correctly parse valid simple labels'
        );
        $this->assertArrayHasKey(
            'key2',
            $pset->perfdata,
            'PerfdataSet does not correctly parse valid simple labels'
        );
        $this->assertArrayHasKey(
            'key3',
            $pset->perfdata,
            'PerfdataSet does not correctly parse valid simple labels'
        );
    }

    public function testWhetherNonQuotedPerfdataLablesWithSpacesAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('key 1=val1 key 1 + 1=val2');
        $this->assertArrayHasKey(
            'key 1',
            $pset->perfdata,
            'PerfdataSet does not correctly parse non quoted labels with spaces'
        );
        $this->assertArrayHasKey(
            'key 1 + 1',
            $pset->perfdata,
            'PerfdataSet does not correctly parse non quoted labels with spaces'
        );
    }

    public function testWhetherValidQuotedPerfdataLabelsAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('\'key 1\'=val1 "key 2"=val2');
        $this->assertArrayHasKey(
            'key 1',
            $pset->perfdata,
            'PerfdataSet does not correctly parse valid quoted labels'
        );
        $this->assertArrayHasKey(
            'key 2',
            $pset->perfdata,
            'PerfdataSet does not correctly parse valid quoted labels'
        );
    }

    public function testWhetherInvalidQuotedPerfdataLabelsAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('\'key 1=val1 key 2"=val2');
        $this->assertArrayHasKey(
            'key 1',
            $pset->perfdata,
            'PerfdataSet does not correctly parse invalid quoted labels'
        );
        $this->assertArrayHasKey(
            'key 2"',
            $pset->perfdata,
            'PerfdataSet does not correctly parse invalid quoted labels'
        );
    }

    /**
     * @depends testWhetherValidSimplePerfdataLabelsAreProperlyParsed
     */
    public function testWhetherAPerfdataSetIsIterable()
    {
        $pset = PerfdataSet::fromString('key=value');
        foreach ($pset as $label => $value) {
            $this->assertEquals('key', $label);
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
