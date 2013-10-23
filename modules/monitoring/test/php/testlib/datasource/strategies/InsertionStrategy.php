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

namespace Test\Monitoring\Testlib\Datasource\Strategies;
use \Test\Monitoring\Testlib\DataSource\TestFixture;

/**
 * Generic interface for Fixture insertion implementations
 *
 * These implementations can create Icinga-compatible Datatsources
 * from TestFixture classes and are therefore rather free in their
 * implementation
 *
 */
interface InsertionStrategy {
    /**
     * Tell the class to use the given ressource as the
     * connection identifier
     *
     * @param $connection   A generic connection identifier,
     *                      the concrete class depends on the implementation
     */
    public function setConnection($connection);

    /**
     * Insert the passed fixture into the datasource and allow
     * the icinga backends to query it.
     *
     * @param TestFixture $fixture
     */
    public function insert(TestFixture $fixture);
}
