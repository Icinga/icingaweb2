<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}


namespace Tests\Icinga\Filter;
use Icinga\Test\BaseTestCase;

use Icinga\Filter\FilterAttribute;
use Icinga\Filter\Filter;
use Icinga\Filter\Type\TextFilter;
use Icinga\Filter\Query\Node;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/QueryProposer.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Filter.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/FilterAttribute.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Domain.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Query/Node.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Query/Tree.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Type/FilterType.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Type/TextFilter.php');

// @codingStandardsIgnoreEnd

class FilterTest extends BaseTestCase
{
    public function testFilterProposals()
    {
        $searchEngine = new Filter();
        $searchEngine->createFilterDomain('host')
            ->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('name')
            );

        $this->assertEquals(
            array(),
            $searchEngine->getProposalsForQuery('Host name Is something'),
            'Assert empty array being returned if no proposal is sensible'
        );

        $this->assertEquals(
            array('{Starts} With'),
            $searchEngine->getProposalsForQuery('Host Name Starts'),
            'Assert operator proposal to occur when entering an attribute'
        );

        $this->assertEquals(
            array('\'...value...\''),
            $searchEngine->getProposalsForQuery('Host name Is test and Hostname contains'),
            'Assert only proposals for the last query part being made'
        );

    }

    public function testSingleQueryTreeCreation()
    {
        $searchEngine = new Filter();
        $searchEngine->createFilterDomain('host')
            ->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('name')
            )->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('test')
            );
        $tree = $searchEngine->createQueryTreeForFilter('Host name is not \'Hans wurst\'');
        $this->assertEquals(
            $tree->root->type,
            Node::TYPE_OPERATOR,
            'Assert a single operator node as the query tree\'s root on a simple query'
        );
    }

    public function testSingleAndQueryTreeCreation()
    {
        $searchEngine = new Filter();
        $searchEngine->createFilterDomain('host')
            ->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('name')
            )->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('test')
            );
        $tree = $searchEngine->createQueryTreeForFilter(
            'Host name is not \'Hans wurst\' and Host test contains something'
        );
        $this->assertEquals(
            $tree->root->type,
            Node::TYPE_AND,
            'Assert an AND node as the query tree\'s root on a simple "and" query'
        );

        $this->assertEquals(array(), $searchEngine->getIgnoredQueryParts(), 'Assert no errors occuring');
        $this->assertEquals(
            $tree->root->left->type, Node::TYPE_OPERATOR, 'Assert a left operator below the root on a single "and" query'
        );
        $this->assertEquals(
            $tree->root->left->left, 'name', 'Assert "name" underneath as the leftmost node on an "and" query'
        );
        $this->assertEquals(
            $tree->root->right->type, Node::TYPE_OPERATOR, 'Assert a left operator below the root on a single "and" query'
        );
        $this->assertEquals(
            $tree->root->right->left, 'test', 'Assert "test" underneath as the leftmost node on an "and" query'
        );
    }

    public function testSingleOrQueryTreeCreation()
    {
        $this->markTestSkipped('OR queries are disabled for now');
        $searchEngine = new Filter();
        $searchEngine->createFilterDomain('host')
            ->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('name')
            )->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('test')
            );

        $tree = $searchEngine->createQueryTreeForFilter(
            'Host name is not \'Hans wurst\' or Host test contains something'
        );

        $this->assertEquals(
            Node::TYPE_OR,
            $tree->root->type,
            'Assert an OR node as the query tree\'s root on a simple "or" query'
        );
        $this->assertEquals(array(), $searchEngine->getIgnoredQueryParts(), 'Assert no errors occuring');
        $this->assertEquals(
            $tree->root->left->type, Node::TYPE_OPERATOR, 'Assert a left operator below the root on a single "or" query'
        );
        $this->assertEquals(
            $tree->root->left->left, 'name', 'Assert "name" underneath as the leftmost node on an "or" query'
        );
        $this->assertEquals(
            $tree->root->right->type, Node::TYPE_OPERATOR, 'Assert a left operator below the root on a single "or" query'
        );
        $this->assertEquals(
            $tree->root->right->left, 'test', 'Assert "test" underneath as the leftmost node on an "or" query'
        );
    }

    public function testMultipleOrQueries()
    {
        $this->markTestSkipped('OR queries are disabled');
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
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('attr4')
            )->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('attr5')
            );
        $query = 'attr1 is not "test" or attr2 is not "test2" or attr3 is 0';
        $tree = $searchEngine->createQueryTreeForFilter($query);
        $this->assertEquals(
            $tree->root->type,
            Node::TYPE_OR,
            'Assert the root node to be or on a multi-or query'
        );
        $this->assertEquals(
            $tree->root->left->type,
            Node::TYPE_OPERATOR,
            'Assert the left node to be an operator on a multi-or query'
        );
        $this->assertEquals(
            $tree->root->right->type,
            Node::TYPE_OR,
            'Assert the right node to be an operator on a multi-or query'
        );

        $this->assertEquals(
            $tree->root->right->right->type,
            Node::TYPE_OPERATOR,
            'Assert the right node to be an operator on a multi-or query'
        );

        $this->assertEquals(
            $tree->root->right->left->type,
            Node::TYPE_OPERATOR,
            'Assert the right node to be an operator on a multi-or query'
        );
    }

    public function testComplexQueryTreeCreation()
    {
        $this->markTestSkipped('OR queries are disabled for now');
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
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('attr4')
            )->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('attr5')
            );


        $query = 'attr1 is not \'Hans wurst\''
            . ' or attr2 contains something '
            . ' and attr3 starts with bla'
            . ' or attr4 contains \'more\''
            . ' and attr5 is test2';
        $tree = $searchEngine->createQueryTreeForFilter($query);
        $this->assertEquals(
            $tree->root->type,
            Node::TYPE_AND,
            'Assert the root node to be an AND (query :"' . $query . '")'
        );
        $this->assertEquals(
            $tree->root->left->type,
            Node::TYPE_OR,
            'Assert the root->left node to be an OR (query :"' . $query . '")'
        );
        $this->assertEquals(
            $tree->root->left->left->type,
            Node::TYPE_OPERATOR,
            'Assert the root->left->left node to be an Operator (query :"' . $query . '")'
        );
        $this->assertEquals(
            $tree->root->left->right->type,
            Node::TYPE_OPERATOR,
            'Assert the root->left->left node to be an Operator (query :"' . $query . '")'
        );
        $this->assertEquals(
            $tree->root->right->type,
            Node::TYPE_AND,
            'Assert the root->right node to be an AND (query :"' . $query . '")'
        );
        $this->assertEquals(
            $tree->root->right->left->type,
            Node::TYPE_OR,
            'Assert the root->right->left node to be an OR (query :"' . $query . '")'
        );
        $this->assertEquals(
            $tree->root->right->left->left->type,
            Node::TYPE_OPERATOR,
            'Assert the root->right->left->left node to be an OPERATOR (query :"' . $query . '")'
        );
        $this->assertEquals(
            $tree->root->right->left->right->type,
            Node::TYPE_OPERATOR,
            'Assert the root->right->left->right node to be an OPERATOR (query :"' . $query . '")'
        );
        $this->assertEquals(
            $tree->root->right->right->type,
            Node::TYPE_OPERATOR,
            'Assert the root->right->right->type node to be an OPERATOR (query :"' . $query . '")'
        );
    }

}