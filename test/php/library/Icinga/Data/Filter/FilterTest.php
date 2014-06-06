<?php

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
 *   problem=true
 *   problem=false
 *
 * Text-Search
 *   service=ping
 *   service!=ping
 *   service=*www*
 *   !service=*www*
 *   hostgroup=www1|www2|!db
 *   hostgroup=[www1,www2]
 *   hostgroup=www1&hostgroup=www2
 *   host[community]=public
 *   _hostcommunity=public
 *
 * Less/greater than:
 *   state>=1
 *   state>0
 *   state<3
 *   state<=2
 *
 * Time
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
/* NOT YET
    public function testFilterMatchesArrayWithWildcards()
    {
        $this->assertTrue(
            Filter::where(
                'service',
                array('nada', 'nothing', 'www*icinga*')
            )->matches($this->row(1))
        );
    }

*/
    public function testFromQueryString()
    {
        $string = 'host_name=localhost&(service_state=1|service_state=2|service_state=3)&service_problem=1';
        $string = 'host=localhost|(host=none&service=ping)|host=www&limit=10&sort=host';

        echo "Parsing: $string\n";
        $pos = 0;
        echo $this->readUnless($string, array('=', '(', '&', '|'), $pos);
        $sign = $this->readChar($string, $pos);
        var_dump($sign);
        echo $this->readUnless($string, array('=', '(', '&', '|'), $pos);
        echo "\n";
    }

    protected function readChar($string, & $pos)
    {
        if (strlen($string) > $pos) {
            return $string[$pos++];
        }
        return false;
    }

    protected function readUnless(& $string, $char, & $pos)
    {
        $buffer = '';
        while ($c = $this->readChar($string, $pos)) {
            if (is_array($char)) {
                if (in_array($c, $char)) {
                    $pos--;
                    break;
                }
            } else {
                if ($c === $char) {
                    $pos--;
                    break;
                }
            }
            $buffer .= $c;
        }
        return $buffer;
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
