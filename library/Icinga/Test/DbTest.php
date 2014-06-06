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

namespace Icinga\Test;

use Icinga\Data\Db\DbConnection;

interface DbTest
{
    /**
     * PHPUnit provider for mysql
     *
     * @return DbConnection
     */
    public function mysqlDb();

    /**
     * PHPUnit provider for pgsql
     *
     * @return DbConnection
     */
    public function pgsqlDb();

    /**
     * PHPUnit provider for oracle
     *
     * @return DbConnection
     */
    public function oracleDb();

    /**
     * Executes sql file on PDO object
     *
     * @param   DbConnection      $resource
     * @param   string          $filename
     *
     * @return  boolean Operational success flag
     */
    public function loadSql(DbConnection $resource, $filename);

    /**
     * Setup provider for testcase
     *
     * @param   string|DbConnection|null $resource
     */
    public function setupDbProvider($resource);
}
