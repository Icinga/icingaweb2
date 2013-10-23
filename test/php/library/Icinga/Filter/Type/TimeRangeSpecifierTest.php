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

use Icinga\Filter\Type\TimeRangeSpecifier;
use Icinga\Filter\Query\Node;
use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../../library/Icinga/Test/BaseTestCase.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Query/Node.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/QueryProposer.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Type/FilterType.php');
require_once realpath(BaseTestCase::$libDir .'/Filter/Type/TimeRangeSpecifier.php');
// @codingStandardsIgnoreEnd

class TimeRangeSpecifierTest extends BaseTestCase
{
    public function testIsValid()
    {
        $tRange = new TimeRangeSpecifier();
        $this->assertTrue(
            $tRange->isValidQuery('since yesterday'),
            'Assert "since yesterday" being a valid time range'
        );

        $this->assertTrue(
            $tRange->isValidQuery('since 2 days'),
            'Assert "since 2 days" being a valid time range'
        );

        $this->assertTrue(
            $tRange->isValidQuery('before tomorrow'),
            'Assert "before tomorrow" being a valid time range'
        );

        $this->assertTrue(
            $tRange->isValidQuery('since "2 hours"'),
            'Assert quotes being recognized'
        );
    }
}