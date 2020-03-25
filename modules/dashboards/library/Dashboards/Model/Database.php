<?php

namespace Icinga\Module\Dashboards\Model;

use ipl\Sql\Connection;

trait Database
{
    protected function getDb()
    {
        return new Connection([
            'db'       => 'mysql',
            'host'     => 'mysql',
            'dbname'   => 'dashboard',
            'username' => 'dashboard',
            'password' => 'dashboard',
            'charset'  => 'utf8mb4'
        ]);
    }

}