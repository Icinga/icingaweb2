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

namespace Test\Modules\Monitoring\Library\Filter\Type;

use Icinga\Module\Monitoring\Filter\Type\StatusFilter;
use Icinga\Filter\Type\TimeRangeSpecifier;
use Icinga\Filter\Query\Node;
use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Test/BaseTestCase.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Query/Node.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/QueryProposer.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Type/FilterType.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Type/TimeRangeSpecifier.php');
require_once realpath(BaseTestCase::$moduleDir .'/monitoring/library/Monitoring/Filter/Type/StatusFilter.php');
// @codingStandardsIgnoreEnd


class StatusFilterTest extends BaseTestCase
{
    public function testOperatorProposal()
    {
        $searchType = StatusFilter::createForHost();
        $this->assertEquals(
            $searchType->getOperators(),
            $searchType->getProposalsForQuery(''),
            'Assert all possible operators to be returned when monitoring status has no further query input'
        );
    }

    public function testStateTypeProposal()
    {
        $searchType = StatusFilter::createForHost();
        $this->assertEquals(
            array('{Pen}ding'),
            $searchType->getProposalsForQuery('is Pen'),
            'Assert StatusFilter to complete partial queries'
        );
    }

    public function testTimeRangeProposal()
    {
        $subFilter = new TimeRangeSpecifier();
        $searchType = StatusFilter::createForHost();
        $this->assertEquals(
            $subFilter->getOperators(),
            $searchType->getProposalsForQuery('is Pending'),
            'Assert StatusFilter to chain TimeRangeSpecifier at the end'
        );

        $this->assertEquals(
            $subFilter->timeExamples,
            $searchType->getProposalsForQuery('is Pending Since'),
            'Assert TimeRange time examples to be proposed'
        );
    }

    public function testQueryNodeCreation()
    {
        $searchType = StatusFilter::createForHost();
        $treeNode = $searchType->createTreeNode('is down', 'host_current_state');
        $this->assertEquals(
            'host_current_state',
            $treeNode->left,
            'Assert the left treenode to represent the state field given to the StatusFilter'
        );
        $this->assertEquals(
            1,
            $treeNode->right[0],
            'Assert the right treenode to contain the numeric status for "Down"'
        );
        $this->assertEquals(
            Node::TYPE_OPERATOR,
            $treeNode->type,
            'Assert the treenode to be an operator node'
        );
        $this->assertEquals(
            Node::OPERATOR_EQUALS,
            $treeNode->operator,
            'Assert the treenode operator to be "Equals"'
        );
    }

    public function testQueryNodeCreationWithTime()
    {
        $searchType = StatusFilter::createForHost();

        $treeNode = $searchType->createTreeNode('is down since yesterday', 'host_current_state');
        $this->assertEquals(
            Node::TYPE_AND,
            $treeNode->type,
            'Assert and and node to be returned when an additional time specifier is appended'
        );
        $this->assertEquals(
            Node::TYPE_OPERATOR,
            $treeNode->left->type,
            'Assert the left node to be the original query (operator)'
        );
        $this->assertEquals(
            'host_current_state',
            $treeNode->left->left,
            'Assert the left node to be the original query (field)'
        );
        $this->assertEquals(
            Node::TYPE_OPERATOR,
            $treeNode->right->type,
            'Assert the right node to be the time specifier query (operator)'
        );
        $this->assertEquals(
            'host_last_state_change',
            $treeNode->right->left,
            'Assert the right node to be the time specifier query (field)'
        );
    }
}