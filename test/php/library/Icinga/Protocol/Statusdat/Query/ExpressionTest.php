<?php

namespace Tests\Icinga\Protocol\Statusdat\Query;

require_once("../../library/Icinga/Protocol/Statusdat/Query/IQueryPart.php");
require_once("../../library/Icinga/Protocol/Statusdat/Query/Expression.php");

use Icinga\Protocol\Statusdat\Query\Expression;

/**
 *
 * Test class for Expression
 * Created Wed, 16 Jan 2013 15:15:16 +0000
 *
 **/
class ExpressionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test for Expression::FromString()
     *
     **/
    public function testFromStringParsing()
    {
        $assertions = array(
            "expression > ?" => "isGreater",
            "expression >= ?" => "isGreaterEq",
            "expression <=   ?" => "isLessEq",
            "expression <  ?" => "isLess",
            "expression =  ?" => "isEqual",
            "expression !=  ?" => "isNotEqual",
            "expression like ?" => "isLike",
            "expression IN ? " => "isIn"
        );

        foreach ($assertions as $query => $callback) {
            $expression = new Expression();
            $value = array(10);
            $expression->fromString($query, $value);
            $this->assertCount(0, $value);
            $this->assertEquals("expression", $expression->getField());
            $this->assertEquals($callback, $expression->CB);
        }
    }

    public function testNumericComparisons()
    {
        $assertions = array( // subarrays are (TEST,MATCHES)
            "expression < ?" => array(5, array(1, 2, 3, 4)),
            "expression <= ?" => array(5, array(1, 2, 3, 4, 5)),
            "expression >=   ?" => array(5, array(5, 6, 7)),
            "expression >  ?" => array(5, array(6, 7)),
            "expression =  ?" => array(5, array(5)),
            "expression !=  ?" => array(5, array(1, 2, 3, 4, 6, 7)),
            "expression IN  ?" => array(array(1, 5, 7), array(1, 5, 7))
        );

        foreach ($assertions as $query => $test) {
            $expression = new Expression();

            $value = array($test[0]);
            $testArray = array(
                (object)array("expression" => 1),
                (object)array("expression" => 2),
                (object)array("expression" => 3),
                (object)array("expression" => 4),
                (object)array("expression" => 5),
                (object)array("expression" => 6),
                (object)array("expression" => 7)
            );
            $expression->fromString($query, $value);
            $this->assertCount(0, $value);
            $result = $expression->filter($testArray);
            foreach ($result as $index) {
                $this->assertContains($index + 1, $test[1]);
            }
        }
    }

    public function testNestedComparison()
    {

        $testArray = array(
            (object)array(
                "expression" => "atest",
                "state" => (object)array("value" => 1)
            ),
            (object)array(
                "expression" => "testa",
                "state" => (object)array("value" => 2)
            )

        );
        $expression = new Expression();
        $value = array(1);
        $expression->fromString("state.value > ?", $value);
        $this->assertCount(0, $value);

        $result = $expression->filter($testArray);
        $this->assertEquals(1, count($result));
        $this->assertEquals(2, $testArray[$result[1]]->state->value);
    }

    public function testNestedComparisonInArray()
    {
        $testArray = array(
            (object)array(
                "expression" => "atest",
                "state" => array((object) array("test"=>"1","test2"=>1))
            ),
            (object)array(
                "expression" => "testa",
                "state" => array((object) array("test"=>"2","test2"=>2))
            )

        );
        $expression = new Expression();
        $value = array(1);
        $expression->fromString("state.test > ?", $value);
        $this->assertCount(0, $value);

        $result = $expression->filter($testArray);
        $this->assertEquals(1, count($result));

    }

    public function testCountQuery()
    {
        $testArray = array(
            (object)array(
                "expression" => "atest",
                "multiple" => array("test"=>"1","test2"=>1)
            ),
            (object)array(
                "expression" => "testa",
                "multiple" => array("test"=>"2","test2"=>2,"test5"=>2,"test1"=>2,"test3"=>2,"test4"=>2)
            )
        );
        $expression = new Expression();
        $value = array(2);
        $expression->fromString("COUNT{multiple} > ?", $value);
        $this->assertCount(0, $value);

        $result = $expression->filter($testArray);
        $this->assertEquals(1, count($result));
    }

    /**
     * Test for Expression::Filter()
     *
     **/
    public function testFilter()
    {
        $this->markTestIncomplete('testFilter is not implemented yet');
    }

}
