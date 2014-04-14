<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Filter;

use Icinga\Filter\Type\TextFilter;
use Icinga\Filter\Query\Node;
use Icinga\Test\BaseTestCase;

class TextFilterTest extends BaseTestCase
{
    public function testOperatorProposal()
    {
        $textFilter = new TextFilter();

        $this->assertEquals(
            $textFilter->getOperators(),
            $textFilter->getProposalsForQuery(''),
            'Assert all operators being proposed when having an empty operator substring'
        );

        $this->assertEquals(
            array('{Con}tains'),
            $textFilter->getProposalsForQuery('con'),
            'Assert one operator being proposed when having a distinguishable operator substring'
        );
        $this->assertEquals(
            array('\'value\'', '{Is} Not'),
            $textFilter->getProposalsForQuery('is'),
            'Assert all operators being proposed when having an ambiguous operator substring'
        );
    }

    public function testGetOperatorAndValueFromQuery()
    {
        $textFilter = new TextFilter();
        list($operator, $value) = $textFilter->getOperatorAndValueFromQuery('is not \'something\'');
        $this->assertEquals(Node::OPERATOR_EQUALS_NOT, $operator, 'Asserting text operators to be split via TextFilter');
        $this->assertEquals('something', $value, 'Asserting quoted values to be recognized in TextFilter');
    }
}