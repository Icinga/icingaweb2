<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Filter;

use \Mockery;
use Icinga\Filter\Query\Node;
use Icinga\Filter\FilterAttribute;
use Icinga\Test\BaseTestCase;

class QueryHandlerTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        $typeMock = Mockery::mock('Icinga\Filter\Type\FilterType');
        $typeMock->shouldReceive('isValidQuery')->with(Mockery::type('string'))->andReturn(true)
            ->shouldReceive('getOperators')->andReturn(array('op1', 'is better than', 'is worse than'))
            ->shouldReceive('getProposalsForQuery')->with(Mockery::type('string'))->andReturnUsing(
                function ($query) use ($typeMock) { return $typeMock->getOperators(); }
            )->shouldReceive('createTreeNode')->with(Mockery::type('string'), Mockery::any())->andReturnUsing(
                function ($query, $leftOperand) { $node = new Node(); $node->left = $leftOperand; return $node; }
        );
        $this->typeMock = $typeMock;
    }

    public function testQueryHandlerSetup()
    {
        $handler = new FilterAttribute($this->typeMock);
        $handler->setField('current_status');
        $handler->setHandledAttributes('State', 'Status', 'Current State');
        $this->assertTrue(
            $handler->queryHasSupportedAttribute('state is down'),
            'Assert attributes to be correctly recognized'
        );
        $this->assertTrue(
            $handler->queryHasSupportedAttribute('current state is down'),
            'Assert more than one attribute to be possible, also with whitespaces'
        );
        $this->assertFalse(
            $handler->queryHasSupportedAttribute('bla status has blah'),
            'Assert invalid attributes to be returned as not supported'
        );
    }

    public function testQueryProposal()
    {
        $handler = new FilterAttribute($this->typeMock);

        $handler->setField('current_status');
        $handler->setHandledAttributes('Status', 'State', 'Current State');

        $this->assertEquals(
            array('Status'),
            $handler->getProposalsForQuery(''),
            'Assert the queryHandler to propose the first attribute if empty string is given'
        );

        $this->assertEquals(
            array('{Current} State'),
            $handler->getProposalsForQuery('current'),
            'Assert the queryHandler to propose sensible attributes if a partial string is given'
        );

        $this->assertEquals(
            array(),
            $handler->getProposalsForQuery('abc'),
            'Assert the queryHandler to return null if no propsal can be made'
        );
    }

    public function testOperatorProposal()
    {
        $handler = new FilterAttribute($this->typeMock);
        $handler->setField('current_status')
            ->setHandledAttributes('status', 'state', 'current state');
        $this->assertEquals(
            array('op1', 'is better than', 'is worse than'),
            $handler->getProposalsForQuery('current state'),
            'Assert all operators being proposed when having a distinguishable attribute'
        );
    }

    public function testAttributeRecognition()
    {
        $handler = new FilterAttribute($this->typeMock);
        $handler->setField('current_status')
            ->setHandledAttributes('status', 'state', 'current state');
        $node = $handler->convertToTreeNode('status is not \’some kind of magic\'');
        $this->assertEquals($node->left, 'current_status', 'Assert status to be set to the field');
    }
}