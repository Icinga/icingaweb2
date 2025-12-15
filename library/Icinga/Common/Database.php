<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Common;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Data\ResourceFactory;
use ipl\Sql\Config as SqlConfig;
use ipl\Sql\Connection;
use LogicException;
use PDO;

/**
 * Trait for accessing the Icinga Web database
 */
trait Database
{
    /**
     * Get a connection to the Icinga Web database
     *
     * @return Connection
     *
     * @throws \Icinga\Exception\ConfigurationError
     */
    protected function getDb(): Connection
    {
        if (! $this->hasDb()) {
            throw new LogicException('Please check if a db instance exists at all');
        }

        $config = new SqlConfig(ResourceFactory::getResourceConfig(
            IcingaConfig::app()->get('global', 'config_resource')
        ));
        if ($config->db === 'mysql') {
            $config->charset = 'utf8mb4';
        }

        // In PHP 8.5+, driver specific constants of the PDO class are deprecated,
        // but the replacements are ony available since php 8.4
        if (version_compare(PHP_VERSION, '8.4.0', '<')) {
            $mysqlInitCommand = PDO::MYSQL_ATTR_INIT_COMMAND;
        } else {
            $mysqlInitCommand = Pdo\Mysql::ATTR_INIT_COMMAND;
        }

        $config->options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ];
        if ($config->db === 'mysql') {
            $config->options[$mysqlInitCommand]
                = "SET SESSION SQL_MODE='STRICT_TRANS_TABLES"
                . ",NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
        }

        return new Connection($config);
    }

    /**
     * Check if db exists
     *
     * @return bool true if a database was found otherwise false
     */
    protected function hasDb()
    {
        return (bool) IcingaConfig::app()->get('global', 'config_resource');
    }
}
