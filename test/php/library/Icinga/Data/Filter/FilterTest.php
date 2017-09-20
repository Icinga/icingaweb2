<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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
                'service' => 'www.icinga.com',
                'state'   => '0',
                'handled' => '0'
            ),
            (object) array(
                'host'    => 'localhost',
                'problem' => '1',
                'service' => 'www.icinga.com',
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
            Filter::where('service', '*com')->matches($this->row(1))
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
            Filter::where('service', 'www*icinga.com')->matches($this->row(1))
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
                    Filter::where('service', 'www.icinga.com'),
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
        $q = '(host=localhost|host=nohost*)&problem&(service=*www*|service=ups*)&state!=1&!handled';
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

    public function testBooleanExpressionIsRenderedCorrectly()
    {
        $filter = Filter::fromQueryString('a&!b');
        $this->assertEquals(
            $filter->toQueryString(),
            'a&!b'
        );
        $this->assertEquals(
            (string) $filter,
            // TODO: I'd prefer to see 'a & !b' here:
            'a & (! b)'
        );
    }

    public function testLeadingAndTrailingWhitespaces()
    {
        $columnWithWhitespaces = Filter::where(' host ', 'localhost');
        $this->assertTrue(
            $columnWithWhitespaces->matches((object) array(
                'host' => 'localhost'
            )),
            'Filter doesn\'t remove leading and trailing whitespaces from columns'
        );
        $expressionWithLeadingWhitespaces = Filter::where('host', ' localhost');
        $this->assertTrue(
            $expressionWithLeadingWhitespaces->matches((object) array(
                'host' => ' localhost'
            )),
            'Filter doesn\'t take leading whitespaces of expressions into account'
        );
        $this->assertFalse(
            $expressionWithLeadingWhitespaces->matches((object) array(
                'host' => ' localhost '
            )),
            'Filter doesn\'t take trailing whitespaces of expressions into account'
        );
        $expressionWithTrailingWhitespaces = Filter::where('host', 'localhost ');
        $this->assertTrue(
            $expressionWithTrailingWhitespaces->matches((object) array(
                'host' => 'localhost '
            )),
            'Filter doesn\'t take trailing whitespaces of expressions into account'
        );
        $this->assertFalse(
            $expressionWithTrailingWhitespaces->matches((object) array(
                'host' => ' localhost '
            )),
            'Filter doesn\'t take leading whitespaces of expressions into account'
        );
        $expressionWithLeadingAndTrailingWhitespaces = Filter::where('host', ' localhost ');
        $this->assertTrue(
            $expressionWithLeadingAndTrailingWhitespaces->matches((object) array(
                'host' => ' localhost '
            )),
            'Filter doesn\'t take leading and trailing whitespaces of expressions into account'
        );
        $this->assertFalse(
            $expressionWithLeadingAndTrailingWhitespaces->matches((object) array(
                'host' => ' localhost  '
            )),
            'Filter doesn\'t take leading and trailing whitespaces of expressions into account'
        );
        $queryStringWithWhitespaces = Filter::fromQueryString(' host = localhost ');
        $this->assertTrue(
            $queryStringWithWhitespaces->matches((object) array(
                'host' => ' localhost '
            )),
            'Filter doesn\'t take leading and trailing whitespaces of expressions in query strings into account'
        );
    }

    /**
     * Test whether special characters inside values are URL-encoded, but the other ones aren't
     */
    public function testSpecialCharacterEscaping()
    {
        $this->assertSame(
            Filter::matchAll(
                Filter::expression('host', '!=', 'localhost'),
                Filter::matchAny(Filter::where('service', 'ping4'), Filter::where('specialchars', '(|&!=)#'))
            )->toQueryString(),
            'host!=localhost&(service=ping4|specialchars=%28%7C%26%21%3D%29%23)'
        );
    }

    private function row($idx)
    {
        return $this->sampleData[$idx];
    }
}
