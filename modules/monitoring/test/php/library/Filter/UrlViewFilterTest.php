<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Module\Monitoring\Library\Filter;

use \Mockery;
use Icinga\Module\Monitoring\Filter\Type\StatusFilter;
use Icinga\Filter\Query\Node;
use Icinga\Filter\Filter;
use Icinga\Filter\Type\TextFilter;
use Icinga\Filter\FilterAttribute;
use Icinga\Module\Monitoring\Filter\UrlViewFilter;
use Icinga\Test\BaseTestCase;

class UrlViewFilterTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->filterMock = Mockery::mock('Icinga\Filter\Filterable');
        $this->filterMock->shouldReceive('isValidFilterTarget')->with(Mockery::any())->andReturn(true)
            ->shouldReceive('getMappedField')->andReturnUsing(function ($f) { return $f; })
            ->shouldReceive('applyFilter')->andReturn(true)
            ->shouldReceive('clearFilter')->andReturnNull()
            ->shouldReceive('addFilter')->with(Mockery::any())->andReturnNull();
    }

    public function testUrlParamCreation()
    {
        $this->markTestSkipped('Or queries are disabled');
        $searchEngine = new Filter();
        $searchEngine->createFilterDomain('host')
            ->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('attr1')
            )->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('attr2')
            )->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('attr3')
            )->registerAttribute(
                FilterAttribute::create(StatusFilter::createForHost())
                    ->setHandledAttributes('attr4')
            )->registerAttribute(
                FilterAttribute::create(StatusFilter::createForHost())
                    ->setHandledAttributes('attr5')
            );
        $query = 'attr1 is not \'Hans wurst\''
            . ' or attr2 contains something '
            . ' and attr3 starts with bla'
            . ' or attr4 is DOWN since "yesterday"'
            . ' and attr5 is UP';

        $tree = $searchEngine->createQueryTreeForFilter($query);
        $filterFactory = new UrlViewFilter($this->filterMock);
        $uri = $filterFactory->fromTree($tree);
        $this->assertEquals(
            'attr1!=Hans+wurst|attr2=%2Asomething%2A&attr3=bla%2A|attr4=1&host_last_state_change>=yesterday&attr5=0',
            $uri,
            'Assert a correct query to be returned when parsing a more complex query ("'. $query .'")'
        );
    }

    public function testTreeFromSimpleKeyValueUrlCreation()
    {
        $filterFactory = new UrlViewFilter($this->filterMock);
        $tree = $filterFactory->parseUrl('attr1!=Hans+Wurst');
        $this->assertEquals(
            $tree->root->type,
            Node::TYPE_OPERATOR,
            'Assert one operator node to exist for a simple filter'
        );
        $this->assertEquals(
            $tree->root->operator,
            Node::OPERATOR_EQUALS_NOT,
            'Assert the operator to be !='
        );
        $this->assertEquals(
            $tree->root->left,
            'attr1',
            'Assert the field to be set correctly'
        );
        $this->assertEquals(
            $tree->root->right[0],
            'Hans Wurst',
            'Assert the value to be set correctly'
        );
    }

    public function testConjunctionFilterInUrl()
    {
        $this->markTestSkipped("OR queries are disabled");

        $filterFactory = new UrlViewFilter($this->filterMock);
        $query = 'attr1!=Hans+Wurst&test=test123|bla=1';
        $tree = $filterFactory->parseUrl($query);
        $this->assertEquals($tree->root->type, Node::TYPE_AND, 'Assert the root of the filter tree to be an AND node');
        $this->assertEquals($filterFactory->fromTree($tree), $query, 'Assert the tree to map back to the query');
    }

    public function testImplicitConjunctionInUrl()
    {
        $this->markTestSkipped("OR queries are disabled");
        $filterFactory = new UrlViewFilter($this->filterMock);
        $query = 'attr1!=Hans+Wurst&test=test123|bla=1|2|3';
        $tree = $filterFactory->parseUrl($query);
        $this->assertEquals($tree->root->type, Node::TYPE_AND, 'Assert the root of the filter tree to be an AND node');
        $this->assertEquals(
            'attr1!=Hans+Wurst&test=test123|bla=1|bla=2|bla=3',
            $filterFactory->fromTree($tree),
            'Assert the tree to map back to the query in an explicit form'
        );
    }

    public function testMissingValuesInQueries()
    {
        $filterFactory = new UrlViewFilter($this->filterMock);
        $queryStr = 'attr1!=Hans+Wurst&test=';
        $tree = $filterFactory->parseUrl($queryStr);
        $query = $filterFactory->fromTree($tree);
        $this->assertEquals('attr1!=Hans+Wurst', $query, 'Assert the valid part of a query to be used');
    }

    public function testErrorInQueries()
    {
        $filterFactory = new UrlViewFilter($this->filterMock);
        $queryStr = 'test=&attr1!=Hans+Wurst';
        $tree = $filterFactory->parseUrl($queryStr);
        $query = $filterFactory->fromTree($tree);
        $this->assertEquals('attr1!=Hans+Wurst', $query, 'Assert the valid part of a query to be used');
    }

    public function testSenselessConjunctions()
    {
        $filterFactory = new UrlViewFilter($this->filterMock);
        $queryStr = 'test=&|/5/|&attr1!=Hans+Wurst';
        $tree = $filterFactory->parseUrl($queryStr);
        $query = $filterFactory->fromTree($tree);
        $this->assertEquals('attr1!=Hans+Wurst', $query, 'Assert the valid part of a query to be used');
    }

    public function testRandomString()
    {
        $filter = '';
        $filterFactory = new UrlViewFilter($this->filterMock);

        for ($i=0; $i<10;$i++) {
            $filter .= str_shuffle('&|ds& wra =!<>|dsgs=,-G');
            $tree = $filterFactory->parseUrl($filter);
        }
    }
}
