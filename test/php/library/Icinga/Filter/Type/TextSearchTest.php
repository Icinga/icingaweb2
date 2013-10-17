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

namespace Tests\Icinga\Filter;

use Icinga\Filter\Type\TextFilter;
use Icinga\Filter\Query\Node;
use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../../library/Icinga/Test/BaseTestCase.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Query/Node.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/QueryProposer.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Type/FilterType.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Type/TextFilter.php');
// @codingStandardsIgnoreEnd

class TextFilterTest extends BaseTestCase
{
    public function testOperatorProposal()
    {
        $textFilter = new TextFilter();

        $this->assertEquals(
            $textFilter->getOperators(),
            $textFilter->getProposalsForQuery(''),
            'Assert all operators being proposed when having an empty operator substring'
        );

        $this->assertEquals(
            array('{Con}tains'),
            $textFilter->getProposalsForQuery('con'),
            'Assert one operator being proposed when having a distinguishable operator substring'
        );
        $this->assertEquals(
            array('\'value\'', '{Is} Not'),
            $textFilter->getProposalsForQuery('is'),
            'Assert all operators being proposed when having an ambiguous operator substring'
        );
    }


    public function testGetOperatorAndValueFromQuery()
    {
        $textFilter = new TextFilter();
        list($operator, $value) = $textFilter->getOperatorAndValueFromQuery('is not \'something\'');
        $this->assertEquals(Node::OPERATOR_EQUALS_NOT, $operator, 'Asserting text operators to be split via TextFilter');
        $this->assertEquals('something', $value, 'Asserting quoted values to be recognized in TextFilter');

    }
}