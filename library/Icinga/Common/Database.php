<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Common;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Data\ResourceFactory;
use Icinga\Util\DBUtils;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Config as SqlConfig;
use ipl\Sql\Connection;
use ipl\Sql\Insert;
use ipl\Sql\QueryBuilder;
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
    protected function getDb()
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

        $config->options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ];
        if ($config->db === 'mysql') {
            $config->options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION SQL_MODE='STRICT_TRANS_TABLES"
                . ",NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
        }

        $conn = new Connection($config);
        if ($conn->getAdapter() instanceof Pgsql) {
            $valuesTransformer = function (&$values) {
                DBUtils::transformValues($values);
            };

            $conn->getQueryBuilder()
                ->on(QueryBuilder::ON_DELETE_ASSEMBLED, $valuesTransformer)
                ->on(QueryBuilder::ON_UPDATE_ASSEMBLED, $valuesTransformer)
                ->on(QueryBuilder::ON_ASSEMBLE_INSERT, function (Insert $insert) {
                    $values = $insert->getValues();
                    foreach ($insert->getValues() as $key => $value) {
                        if (DBUtils::isBinary($value)) {
                            $values[$key] = DBUtils::getBinaryExpr($value);
                        }
                    }

                    $insert->values(array_combine($insert->getColumns(), $values));
                });
        }

        return $conn;
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
