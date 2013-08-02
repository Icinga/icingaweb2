<?php

namespace Icinga\Data\Db;

use Icinga\Data\DatasourceInterface;
use Icinga\Exception\ConfigurationError;
use Zend_Config as ZfConfig;
use Zend_Db as ZfDb;
use PDO;

class Connection implements DatasourceInterface
{
    protected $db;
    protected $config;
    protected $dbtype;

    public function __construct(ZfConfig $config = null)
    {
        $this->config = $config;
        $this->connect();
        $this->init();
    }

    public function select()
    {
        return new Query($this);
    }

    public function getDbType()
    {
        return $this->dbtype;
    }

    public function getDb()
    {
        return $this->db;
    }

    protected function init()
    {
    }

    protected function connect()
    {
        $this->dbtype = $this->config->get('dbtype', 'mysql');

        $options = array(
            ZfDb::AUTO_QUOTE_IDENTIFIERS => false,
            ZfDb::CASE_FOLDING           => ZfDb::CASE_LOWER
        );

        $drv_options = array(
            PDO::ATTR_TIMEOUT            => 2,
            // TODO: Check whether LC is useful. Zend_Db does fetchNum for Oci:
            PDO::ATTR_CASE               => PDO::CASE_LOWER
            // TODO: ATTR_ERRMODE => ERRMODE_EXCEPTION vs ERRMODE_SILENT
        );

        switch ($this->dbtype) {
            case 'mysql':
                $adapter = 'Pdo_Mysql';
                $drv_options[\PDO::MYSQL_ATTR_INIT_COMMAND] =
                    "SET SESSION SQL_MODE='STRICT_ALL_TABLES,NO_ZERO_IN_DATE,"
                  . "NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';";
                // Not using ONLY_FULL_GROUP_BY as of performance impact
                $port = $this->config->get('port', 3306);
                break;
            case 'pgsql':
                $adapter = 'Pdo_Pgsql';
                $port = $this->config->get('port', 5432);
                break;
            case 'oracle':
                $adapter = 'Pdo_Oci';
                // $adapter = 'Oracle';
                $port = $this->config->get('port', 1521);
//                $drv_options[PDO::ATTR_STRINGIFY_FETCHES] = true;

                if ($adapter === 'Oracle') {
                    // Unused right now
                    putenv('ORACLE_SID=XE');
                    putenv('ORACLE_HOME=/u01/app/oracle/product/11.2.0/xe');
                    putenv('PATH=$PATH:$ORACLE_HOME/bin');
                    putenv('ORACLE_BASE=/u01/app/oracle');
                    putenv('NLS_LANG=AMERICAN_AMERICA.UTF8');

                }

                break;
            default:
                throw new ConfigurationError(sprintf(
                    'Backend "%s" is not supported', $type
                ));
        }
        $attributes = array(
            'host'     => $this->config->host,
            'port'     => $port,
            'username' => $this->config->user,
            'password' => $this->config->pass,
            'dbname'   => $this->config->db,
            'options'  => $options,
            'driver_options' => $drv_options
        );
        if ($this->dbtype === 'oracle') {
            $attributes['persistent'] = true;
        }
        $this->db = ZfDb::factory($adapter, $attributes);
        if ($adapter === 'Oracle') {
            $this->db->setLobAsString(false);
        }

        // TODO: ZfDb::FETCH_ASSOC for Oracle?
        $this->db->setFetchMode(ZfDb::FETCH_OBJ);

    }
}
