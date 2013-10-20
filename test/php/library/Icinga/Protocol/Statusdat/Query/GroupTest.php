<?php

namespace Tests\Icinga\Protocol\Statusdat\Query;
require_once("../../library/Icinga/Protocol/Statusdat/Query/IQueryPart.php");
require_once("../../library/Icinga/Protocol/Statusdat/Query/Group.php");

/**
 *
 * Test class for Group
 * Created Wed, 16 Jan 2013 15:15:16 +0000
 *
 **/
use Icinga\Protocol\Statusdat as Statusdat;

class QueryExpressionMock implements Statusdat\Query\IQueryPart
{
    public $rawExpression;
    public $value;
    public $filter = array();

    public function __construct($expression = null, &$value = array())
    {
        $this->value = array_shift($value);
        $this->rawExpression = $expression;

    }

    public function filter(array &$base, &$idx = null)
    {
        return array_intersect(array_values($idx), array_values($this->filter));
    }

    /**
     * Add additional information about the query this filter belongs to
     *
     * @param $query
     * @return mixed
     */
    public function setQuery($query)
    {
        // TODO: Implement setQuery() method.
    }


}

class GroupTest extends \PHPUnit_Framework_TestCase
{
    public function testParsingSingleCondition()
    {
        $testQuery = new Statusdat\Query\Group();
        $value = array(4);
        $testQuery->fromString("numeric_val >= ?", $value, "Tests\Icinga\Protocol\Statusdat\Query\QueryExpressionMock");
        $this->assertCount(1, $testQuery->getItems());
        $this->assertCount(0, $value);

        $expression = $testQuery->getItems();
        $expression = $expression[0];

        $this->assertEquals("numeric_val >= ?", $expression->rawExpression);
        $this->assertEquals(4, $expression->value);

    }

    public function testParsingSimpleAndCondition()
    {
        $testQuery = new Statusdat\Query\Group();
        $value = array(4, 'hosta');
        $testQuery->fromString("numeric_val >= ? AND host_name = ?", $value, "Tests\Icinga\Protocol\Statusdat\Query\QueryExpressionMock");
        $this->assertCount(2, $testQuery->getItems());
        $this->assertCount(0, $value);
        $this->assertEquals("AND", $testQuery->getType());
        $items = $testQuery->getItems();

        $expression0 = $items[0];
        $this->assertEquals("numeric_val >= ?", $expression0->rawExpression);
        $this->assertEquals(4, $expression0->value);

        $expression1 = $items[1];
        $this->assertEquals("host_name = ?", $expression1->rawExpression);
        $this->assertEquals("hosta", $expression1->value);
    }

    public function testParsingSimpleORCondition()
    {
        $testQuery = new Statusdat\Query\Group();
        $value = array(4, 'hosta');
        $testQuery->fromString("numeric_val >= ? OR host_name = ?", $value, "Tests\Icinga\Protocol\Statusdat\Query\QueryExpressionMock");
        $this->assertCount(2, $testQuery->getItems());
        $this->assertCount(0, $value);
        $this->assertEquals("OR", $testQuery->getType());
        $items = $testQuery->getItems();

        $expression0 = $items[0];
        $this->assertEquals("numeric_val >= ?", $expression0->rawExpression);
        $this->assertEquals(4, $expression0->value);

        $expression1 = $items[1];
        $this->assertEquals("host_name = ?", $expression1->rawExpression);
        $this->assertEquals("hosta", $expression1->value);
    }

    public function testParsingExplicitSubgroup()
    {
        $testQuery = new Statusdat\Query\Group();
        $value = array(4, 'service1', 'hosta');
        $testQuery->fromString("numeric_val >= ? AND (service_description = ? OR host_name = ?)", $value, "Tests\Icinga\Protocol\Statusdat\Query\QueryExpressionMock");
        $this->assertCount(2, $testQuery->getItems());
        $this->assertCount(0, $value);
        $this->assertEquals("AND", $testQuery->getType());
        $items = $testQuery->getItems();

        $expression0 = $items[0];
        $this->assertEquals("numeric_val >= ?", $expression0->rawExpression);
        $this->assertEquals(4, $expression0->value);

        $subgroup = $items[1];
        $this->assertInstanceOf("Icinga\Protocol\Statusdat\Query\Group", $subgroup);
        $this->assertEquals("OR", $subgroup->getType());
        $orItems = $subgroup->getItems();

        $expression1 = $orItems[0];
        $this->assertEquals("service_description = ?", $expression1->rawExpression);
        $this->assertEquals("service1", $expression1->value);

        $expression2 = $orItems[1];
        $this->assertEquals("host_name = ?", $expression2->rawExpression);
        $this->assertEquals("hosta", $expression2->value);
    }

    public function testParsingImplicitSubgroup()
    {
        $testQuery = new Statusdat\Query\Group();
        $value = array(4, 'service1', 'hosta');
        $testQuery->fromString("numeric_val >= ? AND service_description = ? OR host_name = ?", $value, "Tests\Icinga\Protocol\Statusdat\Query\QueryExpressionMock");
        $this->assertCount(2, $testQuery->getItems());
        $this->assertCount(0, $value);
        $this->assertEquals("AND", $testQuery->getType());
        $items = $testQuery->getItems();

        $expression0 = $items[0];
        $this->assertEquals("numeric_val >= ?", $expression0->rawExpression);
        $this->assertEquals(4, $expression0->value);

        $subgroup = $items[1];
        $this->assertInstanceOf("Icinga\Protocol\Statusdat\Query\Group", $subgroup);
        $this->assertEquals("OR", $subgroup->getType());
        $orItems = $subgroup->getItems();

        $expression1 = $orItems[0];
        $this->assertEquals("service_description = ?", $expression1->rawExpression);
        $this->assertEquals("service1", $expression1->value);

        $expression2 = $orItems[1];
        $this->assertEquals("host_name = ?", $expression2->rawExpression);
        $this->assertEquals("hosta", $expression2->value);
    }

    public function testAndFilter()
    {
        $testQuery = new Statusdat\Query\Group();
        $testQuery->setType(Statusdat\Query\Group::TYPE_AND);
        $exp1 = new QueryExpressionMock();
        $exp1->filter = array(1, 2, 3, 4, 5, 6, 8);
        $exp2 = new QueryExpressionMock();
        $exp2->filter = array(3, 4, 8);
        $base = array(0, 1, 2, 3, 4, 5, 6, 7, 8);

        $this->assertEquals(array(3, 4, 8), array_values($testQuery->addItem($exp1)->addItem($exp2)->filter($base)));

    }

    public function testOrFilter()
    {
        $testQuery = new Statusdat\Query\Group();
        $testQuery->setType(Statusdat\Query\Group::TYPE_OR);
        $exp1 = new QueryExpressionMock();
        $exp1->filter = array(1, 2, 3);
        $exp2 = new QueryExpressionMock();
        $exp2->filter = array(3, 4, 6, 8);
        $base = array(0, 1, 2, 3, 4, 5, 6, 7, 8);
        $this->assertEquals(array(1, 2, 3, 4, 6, 8), array_values($testQuery->addItem($exp1)->addItem($exp2)->filter($base)));
    }

    public function testCombinedFilter()
    {
        $testQuery_and = new Statusdat\Query\Group();
        $testQuery_and->setType(Statusdat\Query\Group::TYPE_AND);
        $testQuery_or = new Statusdat\Query\Group();
        $testQuery_or->setType(Statusdat\Query\Group::TYPE_OR);
        $base = array(0, 1, 2, 3, 4, 5, 6, 7, 8);

        $and_exp1 = new QueryExpressionMock();
        $and_exp1->filter = array(1, 2, 3, 4, 5, 6, 8);
        $and_exp2 = new QueryExpressionMock();
        $and_exp2->filter = array(3, 4, 8);

        $or_exp1 = new QueryExpressionMock();
        $or_exp1->filter = array(1, 2, 3);
        $or_exp2 = new QueryExpressionMock();
        $or_exp2->filter = array(3, 4, 6, 8);
        $this->assertEquals(array(3, 4, 8), array_values(
                $testQuery_and
                    ->addItem($and_exp1)
                    ->addItem($and_exp2)
                    ->addItem($testQuery_or->addItem($or_exp1)->addItem($or_exp2))
                    ->filter($base))
        );
    }
}

