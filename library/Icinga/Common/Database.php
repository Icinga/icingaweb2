<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Common;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Exception\ConfigurationError;
use ipl\Sql\Config as SqlConfig;
use ipl\Sql\Connection;
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
    protected function getDb()
    {
        if (! $this->hasDb()) {
            throw new ConfigurationError('Cannot load resource config "db". Resource does not exist');
        }

        $config = new SqlConfig(
            IcingaConfig::app()->getSection('db')
        );

        if ($config->db === 'mysql') {
            $config->charset = 'utf8mb4';
        }

        $config->options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ];
        if ($config->db === 'mysql') {
            $config->options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION SQL_MODE='STRICT_TRANS_TABLES"
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
        return ! IcingaConfig::app()->getSection('db')->isEmpty();
    }
}
