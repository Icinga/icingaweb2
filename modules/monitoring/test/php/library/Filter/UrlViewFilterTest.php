<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}
namespace Test\Modules\Monitoring\Library\Filter;

use Icinga\Filter\Filterable;
use Icinga\Filter\Query\Tree;
use Icinga\Module\Monitoring\Filter\Type\StatusFilter;
use Icinga\Filter\Type\TimeRangeSpecifier;
use Icinga\Filter\Query\Node;
use Icinga\Filter\Filter;
use Icinga\Filter\Type\TextFilter;
use Icinga\Filter\FilterAttribute;
use Icinga\Module\Monitoring\Filter\UrlViewFilter;
use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../../library/Icinga/Test/BaseTestCase.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/QueryProposer.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Filter.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/FilterAttribute.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Domain.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Query/Node.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Query/Tree.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Type/FilterType.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Type/TextFilter.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Type/TimeRangeSpecifier.php');
require_once realpath(BaseTestCase::$moduleDir .'/monitoring/library/Monitoring/Filter/Type/StatusFilter.php');
require_once realpath(BaseTestCase::$moduleDir .'/monitoring/library/Monitoring/Filter/UrlViewFilter.php');

class FilterMock implements Filterable
{
    public function isValidFilterTarget($field)
    {
        return true;
    }

    public function getMappedField($field)
    {
        return $field;
    }

    public function applyFilter()
    {
        return true;
    }

    public function clearFilter()
    {
        // TODO: Implement clearFilter() method.
    }

    public function addFilter($filter)
    {
        // TODO: Implement addFilter() method.
    }


}

class UrlViewFilterTest extends BaseTestCase
{
    public function testUrlParamCreation()
    {
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
        $filterFactory = new UrlViewFilter(new FilterMock());
        $uri = $filterFactory->fromTree($tree);
        $this->assertEquals(
            'attr1!=Hans+wurst|attr2=%2Asomething%2A&attr3=bla%2A|attr4=1&host_last_state_change>=yesterday&attr5=0',
            $uri,
            'Assert a correct query to be returned when parsing a more complex query ("'. $query .'")'
        );
    }

    public function testTreeFromSimpleKeyValueUrlCreation()
    {
        $filterFactory = new UrlViewFilter(new FilterMock());
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
            $tree->root->right,
            'Hans Wurst',
            'Assert the value to be set correctly'
        );
    }

    public function testConjunctionFilterInUrl()
    {
        $filterFactory = new UrlViewFilter(new FilterMock());
        $query = 'attr1!=Hans+Wurst&test=test123|bla=1';
        $tree = $filterFactory->parseUrl($query);
        $this->assertEquals($tree->root->type, Node::TYPE_AND, 'Assert the root of the filter tree to be an AND node');
        $this->assertEquals($filterFactory->fromTree($tree), $query, 'Assert the tree to map back to the query');
    }

    public function testImplicitConjunctionInUrl()
    {
        $filterFactory = new UrlViewFilter(new FilterMock());
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
        $filterFactory = new UrlViewFilter(new FilterMock());
        $queryStr = 'attr1!=Hans+Wurst&test=';
        $tree = $filterFactory->parseUrl($queryStr);
        $query = $filterFactory->fromTree($tree);
        $this->assertEquals('attr1!=Hans+Wurst', $query, 'Assert the valid part of a query to be used');
    }

    public function testErrorInQueries()
    {
        $filterFactory = new UrlViewFilter(new FilterMock());
        $queryStr = 'test=&attr1!=Hans+Wurst';
        $tree = $filterFactory->parseUrl($queryStr);
        $query = $filterFactory->fromTree($tree);
        $this->assertEquals('attr1!=Hans+Wurst', $query, 'Assert the valid part of a query to be used');
    }

    public function testSenselessConjunctions()
    {
        $filterFactory = new UrlViewFilter(new FilterMock());
        $queryStr = 'test=&|/5/|&attr1!=Hans+Wurst';
        $tree = $filterFactory->parseUrl($queryStr);
        $query = $filterFactory->fromTree($tree);
        $this->assertEquals('attr1!=Hans+Wurst', $query, 'Assert the valid part of a query to be used');
    }

    public function testRandomString()
    {
        $filter = '';
        $filterFactory = new UrlViewFilter(new FilterMock());

        for ($i=0; $i<10;$i++) {
            $filter .= str_shuffle('&|ds& wra =!<>|dsgs=,-G');
            $tree = $filterFactory->parseUrl($filter);
        }

    }
}
