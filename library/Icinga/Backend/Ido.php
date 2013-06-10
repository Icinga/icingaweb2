<?php

/**
 * Icinga IDO Backend
 *
 * @package Icinga\Backend
 */
namespace Icinga\Backend;

/**
 * This class provides an easy-to-use interface to the IDO database
 *
 * You should usually not directly use this class but go through Icinga\Backend.
 *
 * New MySQL indexes:
 * <code>
 * CREATE INDEX web2_index ON icinga_scheduleddowntime (object_id, is_in_effect);
 * CREATE INDEX web2_index ON icinga_comments (object_id);
 * CREATE INDEX web2_index ON icinga_objects (object_id, is_active); -- (not sure yet)
 * </code>
 *
 * Other possible (history-related) indexes, still subject to tests:
 * CREATE INDEX web2_index ON icinga_statehistory (object_id, state_time DESC);
 * CREATE INDEX web2_index ON icinga_notifications (object_id, instance_id, start_time DESC);
 * CREATE INDEX web2_index ON icinga_downtimehistory (object_id, actual_start_time, actual_end_time);
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Application
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Ido extends AbstractBackend
{
    protected $db;
    protected $dbtype;
    protected $prefix = 'icinga_';

    /**
     * Backend initialization starts here
     *
     * return void
     */
    protected function init()
    {
        $this->connect();
    }

    /**
     * Get our Zend_Db connection
     *
     * return \Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->db;
    }

    public function getDbType()
    {
        return $this->dbtype;
    }

    /**
     * Get our IDO table prefix
     *
     * return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    // TODO: Move elsewhere. Really? Reasons may be: other backends need IDO
    //       access, even in environments running state details without IDO
    protected function connect()
    {
        $this->dbtype = $this->config->get('dbtype', 'mysql');
        $options = array(
            \Zend_Db::AUTO_QUOTE_IDENTIFIERS => false,
            \Zend_Db::CASE_FOLDING           => \Zend_Db::CASE_LOWER
        );
        $drv_options = array(
            \PDO::ATTR_TIMEOUT            => 2,
            // TODO: Check whether LC is useful. Zend_Db does fetchNum for Oci:
            \PDO::ATTR_CASE               => \PDO::CASE_LOWER
            // TODO: ATTR_ERRMODE => ERRMODE_EXCEPTION vs ERRMODE_SILENT
        );
        switch ($this->dbtype) {
            case 'mysql':
                $adapter = 'Pdo_Mysql';
                $drv_options[\PDO::MYSQL_ATTR_INIT_COMMAND] =
                    "SET SESSION SQL_MODE='STRICT_ALL_TABLES,NO_ZERO_IN_DATE,"
                  . "NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';";
                // Not using ONLY_FULL_GROUP_BY as of performance impact
                // TODO: NO_ZERO_IN_DATE as been added with 5.1.11. Is it
                //       ignored by other versions?
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
//                $drv_options[\PDO::ATTR_STRINGIFY_FETCHES] = true;


if ($adapter === 'Oracle') {

putenv('ORACLE_SID=XE');
putenv('ORACLE_HOME=/u01/app/oracle/product/11.2.0/xe');
putenv('PATH=$PATH:$ORACLE_HOME/bin');
putenv('ORACLE_BASE=/u01/app/oracle');
putenv('NLS_LANG=AMERICAN_AMERICA.UTF8');

}


                $this->prefix = '';
                break;
            default:
                throw new \Exception(sprintf(
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
        $this->db = \Zend_Db::factory($adapter, $attributes);
        if ($adapter === 'Oracle') {
            $this->db->setLobAsString(false);
        }
        $this->db->setFetchMode(\Zend_Db::FETCH_OBJ);
        // $this->db->setFetchMode(\Zend_Db::FETCH_ASSOC);

    }



    /// *** TODO: EVERYTHING BELOW THIS LINE WILL BE MOVED AWAY *** ///


    // UGLY temporary host fetch
    public function fetchHost($host)
    {
        $object = \Icinga\Objects\Service::fromBackend(
            $this->select()
                 ->from('servicelist')
                 ->where('so.name1 = ?', $host)
                 ->fetchRow()
        );
        $object->customvars = $this->fetchCustomvars($host);
        return $object;
    }

    // UGLY temporary service fetch
    public function fetchService($host, $service)
    {
        $object = \Icinga\Objects\Service::fromBackend(
            $this->select()
                ->from('servicelist')
                ->where('so.name1 = ?', $host)
                ->where('so.name2 = ?', $service)
                ->fetchRow()
        );
        $object->customvars = $this->fetchCustomvars($host, $service);
        return $object;
    }

    public function fetchCustomvars($host, $service = null)
    {
        if ($this->dbtype === 'oracle') return (object) array();

        $select = $this->db->select()->from(
            array('cv' => $this->prefix . 'customvariablestatus'),
            array(
                // 'host_name'           => 'cvo.name1',
                // 'service_description' => 'cvo.name2',
                'name'                => 'cv.varname',
                'value'               => 'cv.varvalue',
            )
        )->join(
            array('cvo' => $this->prefix . 'objects'),
            'cvo.object_id = cv.object_id',
            array()
        );
        $select->where('name1 = ?', $host);
        if ($service === null) {
            $select->where('objecttype_id = 1');
        } else {
            $select->where('objecttype_id = 1');
            $select->where('name2 = ?', $service);
        }
        $select->where('is_active = 1')->order('cv.varname');
        return (object) $this->db->fetchPairs($select);
    }

    // TODO: Move to module!

    public function fetchHardStatesForBpHosts($hosts)
    {
        return $this->fetchStatesForBp($hosts, 'last_hard_state');
    }

    public function fetchSoftStatesForBpHosts($hosts)
    {
        return $this->fetchStatesForBp($hosts, 'current_state');
    }
    
    public function fetchStatesForBp($hosts, $state_column = 'last_hard_state')
    {
        $select_hosts = $this->db->select()->from(
            array('hs' => $this->prefix . 'hoststatus'),
            array(
                'state' => 'hs.' . $state_column,
                'ack'   => 'hs.problem_has_been_acknowledged',
                'in_downtime' => 'CASE WHEN (d.object_id IS NULL) THEN 0 ELSE 1 END',
                'combined' => 'hs.current_state << 2 + hs.problem_has_been_acknowledged << 1 + CASE WHEN (d.object_id IS NULL) THEN 0 ELSE 1 END'
            )
        )->joinRight(
            array('o' => $this->prefix . 'objects'),
            'hs.host_object_id = o.object_id',
            array(
				'object_id' => 'o.object_id',
                'hostname' => 'o.name1',
                'service'  => '(NULL)'
            )
        )->joinLeft(
            array('d' => $this->prefix . 'scheduleddowntime'),
            'o.object_id = d.object_id'
          . ' AND d.was_started = 1'
          . ' AND d.scheduled_end_time > NOW()'
          . ' AND d.actual_start_time < NOW()',
            array()
        )->where('o.name1 IN (?)', $hosts)
         ->where('o.objecttype_id = 1')
         ->where('o.is_active = 1');

        $select_services = $this->db->select()->from(
            array('ss' => $this->prefix . 'servicestatus'),
            array(
                'state' => 'ss.' . $state_column,
                'ack'   => 'ss.problem_has_been_acknowledged',
                'in_downtime' => 'CASE WHEN (d.object_id IS NULL) THEN 0 ELSE 1 END',
                'combined' => 'ss.current_state << 2 + ss.problem_has_been_acknowledged << 1 + CASE WHEN (d.object_id IS NULL) THEN 0 ELSE 1 END'
            )
        )->joinRight(
            array('o' => $this->prefix . 'objects'),
            'ss.service_object_id = o.object_id',
            array(
				'object_id' => 'o.object_id',
                'hostname' => 'o.name1',
                'service'  => 'o.name2'
            )
        )->joinLeft(
            array('d' => $this->prefix . 'scheduleddowntime'),
            'o.object_id = d.object_id'
          . ' AND d.was_started = 1'
          . ' AND d.scheduled_end_time > NOW()'
          . ' AND d.actual_start_time < NOW()',
            array()
        )->where('o.name1 IN (?)', $hosts)
         ->where('o.is_active = 1')
         ->where('o.objecttype_id = 2');

        $union = $this->db->select()->union(
            array(
                '(' . $select_hosts . ')',      // ZF-4338 :-(
                '(' . $select_services . ')',
            ),
            // At least on MySQL UNION ALL seems to be faster than UNION in
            // most situations, as it doesn't care about duplicates
            \Zend_Db_Select::SQL_UNION_ALL
        )->order('hostname')->order('service');

        return $this->db->fetchAll($union);
    }


}

