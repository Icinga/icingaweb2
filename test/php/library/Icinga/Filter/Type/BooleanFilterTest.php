<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Filter;

use Icinga\Filter\Type\BooleanFilter;
use Icinga\Filter\Query\Node;
use Icinga\Test\BaseTestCase;

class BooleanFilterTest extends BaseTestCase
{
    public function testOperatorProposal()
    {
        $filter = new BooleanFilter(array());
        $this->assertEquals(
            $filter->getOperators(),
            $filter->getProposalsForQuery(''),
            'Assert all operators to be returned for an empty query'
        );
    }

    public function testFieldProposal()
    {
        $filter = new BooleanFilter(array(
            'host_problem'      => 'With Problem',
            'host_is_flapping'  => 'Flapping',
        ));
        $this->assertEquals(
            array('With Problem', 'Flapping', '{Is} Not'),
            $filter->getProposalsForQuery('is'),
            'Assert fields to be proposed when an operator is given in boolean fields'
        );
        $this->assertEquals(
            array('{With} Problem'),
            $filter->getProposalsForQuery('is with'),
            'Assert partial fields being recognized in boolean filter queries'
        );
    }

    public function testKeyProposal()
    {
        $filter = new BooleanFilter(array(
            'host_problem'      => 'With Problem',
            'host_is_flapping'  => 'Flapping',
        ));

        $this->assertEquals(
            array('{host_pr}oblem'),
            $filter->getProposalsForQuery('is host_pr'),
            'Assert keys being used when they match instead of the values'
        );
    }

    public function testTimeRangeProposal()
    {
        $filter = new BooleanFilter(array(
            'host_problem'      => 'With Problem',
            'host_is_flapping'  => 'Flapping',
        ), 'time_field');

        $this->assertEquals(
            array('Since', 'Before'),
            $filter->getProposalsForQuery('is with problem'),
            'Assert timerange proposals being made if "noTime" is not set on creation'
        );
    }

    public function testQueryValidation()
    {
        $filter = new BooleanFilter(array(
            'host_problem'      => 'With Problem',
            'host_is_flapping'  => 'Flapping',
        ));
        $this->assertTrue($filter->isValidQuery('is with problem'), 'Assert valid queries to be recognized');
        $this->assertFalse($filter->isValidQuery('is problem'), 'Assert invalid queries to be recognized');
    }

    public function testQueryNodeCreation()
    {
        $filter = new BooleanFilter(array(
            'host_problem'       => 'With Problem',
            'host_is_flapping'   => 'Flapping'
        ));
        $node = $filter->createTreeNode('is with problem', 'host_status');
        $this->assertEquals('host_problem', $node->left, 'Assert the left part of the node to be host_problem');
        $this->assertEquals(Node::OPERATOR_EQUALS, $node->operator, 'Assert the operator to be equals');
        $this->assertEquals(1, $node->right[0], 'Assert the value to be 1');

        $node = $filter->createTreeNode('is not with problem', 'host_status');
        $this->assertEquals('host_problem', $node->left, 'Assert the left part of the node to be host_problem');
        $this->assertEquals(Node::OPERATOR_EQUALS, $node->operator, 'Assert the operator to be equals');
        $this->assertEquals(0, $node->right[0], 'Assert the value to be 0 for not equals');
    }

    public function testTimeQueryNodeCreation()
    {
        $filter = new BooleanFilter(array(
            'host_problem'       => 'With Problem',
            'host_is_flapping'   => 'Flapping'
        ), 'time_node');

        $node = $filter->createTreeNode('is with problem since 1 hour', 'host_status');

        $this->assertEquals(Node::TYPE_AND, $node->type, 'Assert the node to be an AND node');

        $this->assertEquals('time_node', $node->left->left, 'Assert the left part of the node to be time filter');
        $this->assertEquals(Node::OPERATOR_GREATER_EQ, $node->left->operator, 'Assert the operator to be greater eq');
        $this->assertEquals('-1 hour', $node->left->right[0], 'Assert the value to be the strotime info');

        $this->assertEquals('host_problem', $node->right->left, 'Assert the right part of the node to be host_problem');
        $this->assertEquals(Node::OPERATOR_EQUALS, $node->right->operator, 'Assert the operator to be equals');
        $this->assertEquals(1, $node->right->right[0], 'Assert the value to be 1');
    }
}