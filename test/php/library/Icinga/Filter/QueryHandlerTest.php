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

use Icinga\Filter\Query\Node;
use Icinga\Filter\FilterAttribute;
use Icinga\Filter\Type\FilterType;
use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Query/Node.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/QueryProposer.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Domain.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/FilterAttribute.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Type/FilterType.php');

class TypeMock extends FilterType
{
    public function isValidQuery($query)
    {
        return true;
    }

    public function createTreeNode($query, $leftOperand)
    {
        $node = new Node();
        $node->left = $leftOperand;
        return $node;
    }


    public function getProposalsForQuery($query)
    {
        return $this->getOperators();
    }

    public function getOperators()
    {
        return array('op1', 'is better than', 'is worse than');
    }

}

class QueryHandlerTest extends BaseTestCase
{
    public function testQueryHandlerSetup()
    {
        $handler = new FilterAttribute(new TypeMock());
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
        $handler = new FilterAttribute(new TypeMock());

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

        $handler = new FilterAttribute(new TypeMock());
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
        $handler = new FilterAttribute(new TypeMock());
        $handler->setField('current_status')
            ->setHandledAttributes('status', 'state', 'current state');
        $node = $handler->convertToTreeNode('status is not \’some kind of magic\'');
        $this->assertEquals($node->left, 'current_status', 'Assert status to be set to the field');
    }

}