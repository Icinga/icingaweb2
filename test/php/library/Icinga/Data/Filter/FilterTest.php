<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Data;

use Icinga\Test\BaseTestCase;
use Icinga\Data\Filter\Filter;

/**
 * Tests for Icinga\Data\Filter
 *
 * Still unfinished, things I'd like to see here:
 *
 * Boolean:
 *   problem
 *   !problem
 *   !!problem
 *   problem=0
 *   problem=1
 *
 * Text-Search
 *   service=ping
 *   service!=ping
 *   service=*www*
 *   !service=*www*
 *   hostgroup=(www1|www2)
 *   hostgroup=www1&hostgroup=www2
 *   _host_community=public
 *
 * Less/greater than:
 *   state>=1
 *   state>0
 *   state<3
 *   state<=2
 *
 * Time
 *
 * Some complex filters that should be tested:
 *
 * !host=a!*(n  => NOT host = "a!*(n"
 *
 * !service_problem&service_handled (regression for #6554)
 *
 * Additional nestings just to test a bunch of not/and combinations:
 *
 * service_problem=1&!((!((!(service_handled=1)&host_problem=1)))&!host=abc*
 *
 * !service_problem=1&(((((service_handled=1)))))
 *
 * !service_problem&(((((service_handled)))))&host=abc*
 *
 * !service_problem&(((((service_handled))&!service_problem=1)))
 *
 * !!(!((host<net*))&!(!(service=srva&host=hosta)|(service=srvb&host=srvb)))
 *
 * What about ~?
 */
class FilterTest extends BaseTestCase
{
    private $nsPrefix = 'Icinga\\Data\\Filter\\';

    private $sampleData;

    public function setUp()
    {
        parent::setUp();
        $this->sampleData = array(
            (object) array(
                'host'    => 'localhost',
                'problem' => '1',
                'service' => 'ping',
                'state'   => '2',
                'handled' => '1'
            ),
            (object) array(
                'host'    => 'localhost',
                'problem' => '1',
                'service' => 'www.icinga.org',
                'state'   => '0',
                'handled' => '0'
            ),
            (object) array(
                'host'    => 'localhost',
                'problem' => '1',
                'service' => 'www.icinga.org',
                'state'   => '1',
                'handled' => '0'
            )
        );
    }
// TODO: expect exception when filtering for invalid column

    public function testWhetherMatchAnyReturnsFilterOr()
    {
        $this->assertInstanceOf($this->nsPrefix . 'FilterOr', Filter::matchAny());
    }

    public function testWhetherMatchAllReturnsFilterAnd()
    {
        $this->assertInstanceOf($this->nsPrefix . 'FilterAnd', Filter::matchAll());
    }

    public function testWildcardFilterMatchesBeginning()
    {
        $this->assertTrue(
            Filter::where('service', 'www*')->matches($this->row(1))
        );
    }

    public function testWildcardFilterMissmatchesBeginning()
    {
        $this->assertFalse(
            Filter::where('service', 'www*')->matches($this->row(0))
        );
    }

    public function testWildcardFilterHandlesBeginning()
    {
        $this->assertFalse(
            Filter::where('service', 'ww.*')->matches($this->row(1))
        );
    }

    public function testWildcardFilterMatchesEnding()
    {
        $this->assertTrue(
            Filter::where('service', '*org')->matches($this->row(1))
        );
    }

    public function testWildcardFilterMissMatchesEnding()
    {
        $this->assertFalse(
            Filter::where('service', '*net')->matches($this->row(1))
        );
    }

    public function testWildcardFilterMatchesDot()
    {
        $this->assertTrue(
            Filter::where('service', 'www*icinga.org')->matches($this->row(1))
        );
    }

    public function testFilterMatchesArray()
    {
        $this->assertTrue(
            Filter::where(
                'service',
                array('nada', 'nothing', 'ping')
            )->matches($this->row(0))
        );
    }

    public function testFilterMissMatchesArray()
    {
        $this->assertFalse(
            Filter::where(
                'service',
                array('nada', 'nothing', 'ping')
            )->matches($this->row(1))
        );
    }

    public function testManualFilterCreation()
    {
        $filter = Filter::matchAll(
            Filter::where('host', '*localhost*'),
            Filter::matchAny(
                Filter::where('service', 'ping'),
                Filter::matchAll(
                    Filter::where('service', 'www.icinga.org'),
                    Filter::where('state', '0')
                )
            )
        );
        $this->assertTrue($filter->matches($this->row(0)));
        $this->assertTrue($filter->matches($this->row(1)));
        $this->assertFalse($filter->matches($this->row(2)));
    }

    public function testComplexFilterFromQueryString()
    {
        $q = 'host=localhost|nohost*&problem&service=*www*|ups*&state!=1&!handled';
        $filter = Filter::fromQueryString($q);
        $this->assertFalse($filter->matches($this->row(0)));
        $this->assertTrue($filter->matches($this->row(1)));
        $this->assertFalse($filter->matches($this->row(2)));
    }

    public function testCloningDeepFilters()
    {
        $a = Filter::where('a', 'a1');
        $b = Filter::where('b', 'b1');
        $c = Filter::matchAll($a, $b);
        $d = clone $c;
        $b->setColumn('bb');
        $this->assertNotEquals((string) $c, (string) $d);
    }

    private function row($idx)
    {
        return $this->sampleData[$idx];
    }
}
