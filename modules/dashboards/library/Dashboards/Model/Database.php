<?php

namespace Icinga\Module\Dashboards\Model;

use ipl\Sql\Config;
use ipl\Sql\Connection;
use PDO;

trait Database
{
    protected function getDb()
    {
        $config = new Config([
            'db'       => 'mysql',
            'host'     => 'mysql',
            'dbname'   => 'dashboard',
            'username' => 'dashboard',
            'password' => 'dashboard',
            'charset'  => 'utf8mb4'
        ]);

        $config->options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE"
                . ",ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
        ];

        return new Connection($config);
    }
}
