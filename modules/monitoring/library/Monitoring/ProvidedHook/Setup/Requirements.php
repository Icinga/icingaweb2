<?php

namespace Icinga\Module\Monitoring\ProvidedHook\Setup;

use Icinga\Module\Setup\Requirement\ClassRequirement;
use Icinga\Module\Setup\Requirement\PhpModuleRequirement;
use Icinga\Module\Setup\RequirementSet;
use Icinga\Module\Setup\Hook\RequirementsHook;

class Requirements extends RequirementsHook
{
    public function backend()
    {
        $set = new RequirementSet(false, RequirementSet::MODE_OR);

        $set->merge($this->mysql());
        $set->merge($this->pgsql());

        return $set;
    }

    public function mysql()
    {
        $set = new RequirementSet(true);
        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'pdo_mysql',
            'alias'         => 'PDO-MySQL',
            'description'   => mt(
                'monitoring',
                'To access the IDO stored in a MySQL database the PDO-MySQL module for PHP is required.'
            )
        )));
        $set->add(new ClassRequirement(array(
            'optional'      => true,
            'condition'     => 'Zend_Db_Adapter_Pdo_Mysql',
            'alias'         => mt('monitoring', 'Zend database adapter for MySQL'),
            'description'   => mt(
                'monitoring',
                'The Zend database adapter for MySQL is required to access a MySQL database.'
            )
        )));

        return $set;
    }

    public function pgsql()
    {
        $pgsql = new RequirementSet(true);
        $pgsql->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'pdo_pgsql',
            'alias'         => 'PDO-PostgreSQL',
            'description'   => mt(
                'monitoring',
                'To access the IDO stored in a PostgreSQL database the PDO-PostgreSQL module for PHP is required.'
            )
        )));
        $pgsql->add(new ClassRequirement(array(
            'optional'      => true,
            'condition'     => 'Zend_Db_Adapter_Pdo_Pgsql',
            'alias'         => mt('monitoring', 'Zend database adapter for PostgreSQL'),
            'description'   => mt(
                'monitoring',
                'The Zend database adapter for PostgreSQL is required to access a PostgreSQL database.'
            )
        )));

        return $pgsql;
    }

    public function getRequirements()
    {
        $set = new RequirementSet();

        $set->merge($this->backend());

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'curl',
            'alias'         => 'cURL',
            'description'   => mt(
                'monitoring',
                'To send external commands over Icinga 2\'s API the cURL module for PHP is required.'
            )
        )));

        return $set;
    }
}
