<?php
/* Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

namespace Icinga\Common;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Data\ResourceFactory;
use ipl\Sql\Config as SqlConfig;
use ipl\Sql\Connection;
use PDO;

trait Database
{
    protected function getDb()
    {
        $config = new SqlConfig(ResourceFactory::getResourceConfig(
            IcingaConfig::app()->get('global', 'config_resource')
        ));
        $config->options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE"
                . ",ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
        ];

        $conn = new Connection($config);

        return $conn;
    }
}
