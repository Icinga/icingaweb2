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

namespace Icinga\Data\Db;

use PDO;
use Zend_Config;
use Zend_Db;
use Icinga\Data\DatasourceInterface;
use Icinga\Exception\ConfigurationError;

/**
 * Encapsulate database connections and query creation
 */
class Connection implements DatasourceInterface
{
    /**
     * Connection config
     *
     * @var Zend_Config
     */
    private $config;

    /**
     * Database type
     *
     * @var string
     */
    private $dbType;

    private $conn;

    private $tablePrefix = '';

    private static $genericAdapterOptions = array(
        Zend_Db::AUTO_QUOTE_IDENTIFIERS => false,
        Zend_Db::CASE_FOLDING           => Zend_Db::CASE_LOWER
    );

    private static $driverOptions = array(
        PDO::ATTR_TIMEOUT   => 2,
        PDO::ATTR_CASE      => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE   => PDO::ERRMODE_EXCEPTION
    );

    /**
     * Create a new connection object
     *
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config = null)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Prepare query object
     *
     * @return Query
     */
    public function select()
    {
        return new Query($this);
    }

    /**
     * Getter for database type
     *
     * @return string
     */
    public function getDbType()
    {
        return $this->dbType;
    }

    /**
     * Getter for database object
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Create a new connection
     */
    private function connect()
    {
        $genericAdapterOptions  = self::$genericAdapterOptions;
        $driverOptions          = self::$driverOptions;
        $adapterParamaters      = array(
            'host'              => $this->config->host,
            'username'          => $this->config->username,
            'password'          => $this->config->password,
            'dbname'            => $this->config->dbname,
            'options'           => & $genericAdapterOptions,
            'driver_options'    => & $driverOptions
        );
        $this->dbType = strtolower($this->config->get('db', 'mysql'));
        switch ($this->dbType) {
            case 'mysql':
                $adapter = 'Pdo_Mysql';
                /*
                 * Set MySQL server SQL modes to behave as closely as possible to Oracle and PostgreSQL. Note that the
                 * ONLY_FULL_GROUP_BY mode is left on purpose because MySQL requires you to specify all non-aggregate columns
                 * in the group by list even if the query is grouped by the master table's primary key which is valid
                 * ANSI SQL though. Further in that case the query plan would suffer if you add more columns to the group by
                 * list.
                 */
                $driverOptions[PDO::MYSQL_ATTR_INIT_COMMAND] =
                    'SET SESSION SQL_MODE=\'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,'
                    . 'NO_AUTO_CREATE_USER,ANSI_QUOTES,PIPES_AS_CONCAT,NO_ENGINE_SUBSTITUTION\';';
                $adapterParamaters['port'] = $this->config->get('port', 3306);
                break;
            case 'pgsql':
                $adapter = 'Pdo_Pgsql';
                $adapterParamaters['port'] = $this->config->get('port', 5432);
                break;
//            case 'oracle':
//                if ($this->dbtype === 'oracle') {
//                    $attributes['persistent'] = true;
//                }
//                $this->db = ZfDb::factory($adapter, $attributes);
//                if ($adapter === 'Oracle') {
//                    $this->db->setLobAsString(false);
//                }
//                break;
            default:
                throw new ConfigurationError(
                    sprintf(
                        'Backend "%s" is not supported', $this->dbType
                    )
                );
        }
        $this->conn = Zend_Db::factory($adapter, $adapterParamaters);
        $this->conn->setFetchMode(Zend_Db::FETCH_OBJ);
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
        return $this;
    }
}
