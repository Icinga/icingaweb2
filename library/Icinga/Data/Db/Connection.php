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

use \PDO;
use \Zend_Config;
use \Zend_Db;
use \Zend_Db_Adapter_Abstract;
use \Icinga\Application\DbAdapterFactory;
use \Icinga\Data\DatasourceInterface;
use \Icinga\Exception\ConfigurationError;
use \Icinga\Application\Logger;

/**
 * Encapsulate database connections and query creation
 */
class Connection implements DatasourceInterface
{
    /**
     * Database connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Backend configuration
     *
     * @var Zend_Config
     */
    protected $config;

    /**
     * Database type
     *
     * @var string
     */
    protected $dbType;

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
        $resourceName = $this->config->get('resource');
        $this->db = DbAdapterFactory::getDbAdapter($resourceName);

        if ($this->db->getConnection() instanceof PDO) {
            $this->dbType = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        } else {
            $this->dbType = strtolower(get_class($this->db->getConnection()));
        }
        $this->db->setFetchMode(Zend_Db::FETCH_OBJ);

        if ($this->dbType === null) {
            Logger::warn('Could not determine database type');
        }

        if ($this->dbType === 'oci') {
            $this->dbType = 'oracle';
        }
    }
}
