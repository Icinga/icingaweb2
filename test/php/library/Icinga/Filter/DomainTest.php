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

use Icinga\Filter\FilterAttribute;
use Icinga\Filter\Query\Node;
use Icinga\Filter\Type\TextFilter;
use Icinga\Test\BaseTestCase;
use Icinga\Filter\Domain;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/QueryProposer.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/FilterAttribute.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Domain.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Query/Node.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Type/FilterType.php');
require_once realpath(BaseTestCase::$libDir . '/Filter/Type/TextFilter.php');

// @codingStandardsIgnoreEnd

class DomainTest extends BaseTestCase
{

    public function testDomainRecognitionInQueryString()
    {
        $domain = new Domain('host');
        $queryWithWhitespace = ' host is up';
        $camelCaseQuery = 'HOsT is down';
        $invalidQuery = 'Horst host Host';
        $this->assertTrue($domain->handlesQuery($queryWithWhitespace), 'Assert the domain to ignore starting whitespaces');
        $this->assertTrue($domain->handlesQuery($camelCaseQuery), 'Assert the domain to be case insensitive');
        $this->assertFalse($domain->handlesQuery($invalidQuery), 'Assert wrong domains to be recognized');
    }

    public function testQueryProposal()
    {
        $domain = new Domain('host');
        $attr = new TextFilter();
        $queryHandler = new FilterAttribute($attr);
        $domain->registerAttribute($queryHandler->setHandledAttributes('name', 'description'));
        $this->assertEquals(
            array('name'),
            $domain->getProposalsForQuery(''),
            'Assert the name being returned when empty query is provided to domain'
        );
        $this->assertEquals(
            array('\'value\'', '{Is} Not'),
            $domain->getProposalsForQuery('host name is'),
            'Assert mixed operator extension and value proposal being returned when provided a partial query'
        );
        $this->assertEquals(
            array('\'value\''),
            $domain->getProposalsForQuery('name is not'),
            'Assert only the value to be returned when operator is fully given'
        );
        $this->assertEquals(
            array(),
            $domain->getProposalsForQuery('sagsdgsdgdgds')
        );
    }

    public function testGetQueryTree()
    {
        $domain = new Domain('host');
        $attr = new TextFilter();
        $queryHandler = new FilterAttribute($attr);
        $domain->registerAttribute($queryHandler->setField('host_name')->setHandledAttributes('name', 'description'));
        $node = $domain->convertToTreeNode('Host name is \'my host\'');
        $this->assertEquals($node->type, Node::TYPE_OPERATOR, 'Assert a domain to produce operator query nodes');
        $this->assertEquals($node->left, 'host_name', 'Assert a domain to insert the field as the left side of a treenode');
        $this->assertEquals($node->right, 'my host', 'Assert a domain to insert the value as the right side of a treenode');
        $this->assertEquals($node->operator, Node::OPERATOR_EQUALS, 'Assert the correct operator to be set in a single query');
    }

}