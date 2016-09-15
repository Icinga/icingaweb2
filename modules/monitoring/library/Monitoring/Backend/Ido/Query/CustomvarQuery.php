<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Application\Config;

class CustomvarQuery extends IdoQuery
{
    protected $columnMap = array(
        'instances' => array(
            'instance_name' => 'i.instance_name'
        ),
        'customvariablestatus' => array(
            'varname'  => 'cvs.varname',
            'varvalue' => 'cvs.varvalue',
            'is_json'  => 'cvs.is_json',
        ),
        'objects' => array(
            'host'                => 'cvo.name1 COLLATE latin1_general_ci',
            'host_name'           => 'cvo.name1',
            'service'             => 'cvo.name2 COLLATE latin1_general_ci',
            'service_description' => 'cvo.name2',
            'contact'             => 'cvo.name1 COLLATE latin1_general_ci',
            'contact_name'        => 'cvo.name1',
            'object_type'         => "CASE cvo.objecttype_id WHEN 1 THEN 'host' WHEN 2 THEN 'service' WHEN 10 THEN 'contact' ELSE 'invalid' END",
            'object_type_id'      => 'cvo.objecttype_id'
//            'object_type'         => "CASE cvo.objecttype_id WHEN 1 THEN 'host' WHEN 2 THEN 'service' WHEN 3 THEN 'hostgroup' WHEN 4 THEN 'servicegroup' WHEN 5 THEN 'hostescalation' WHEN 6 THEN 'serviceescalation' WHEN 7 THEN 'hostdependency' WHEN 8 THEN 'servicedependency' WHEN 9 THEN 'timeperiod' WHEN 10 THEN 'contact' WHEN 11 THEN 'contactgroup' WHEN 12 THEN 'command' ELSE 'other' END"
        ),
    );

    public function where($expression, $parameters = null)
    {
        $types = array('host' => 1, 'service' => 2, 'contact' => 10);
        if ($expression === 'object_type') {
            parent::where('object_type_id', $types[$parameters]);
        } else {
            parent::where($expression, $parameters);
        }
        return $this;
    }

    protected function joinBaseTables()
    {
        if (version_compare($this->getIdoVersion(), '1.12.0', '<')) {
            $this->columnMap['customvariablestatus']['is_json'] = '(0)';
        }

        if (! (bool) Config::module('monitoring')->get('ido', 'use_customvar_status_table', true)) {
            $table = 'customvariables';
        } else {
            $table = 'customvariablestatus';
        }

        $this->select->from(
            array('cvs' => $this->prefix . $table),
            array()
        )->join(
            array('cvo' => $this->prefix . 'objects'),
            'cvs.object_id = cvo.object_id AND cvo.is_active = 1',
            array()
        );
        $this->joinedVirtualTables = array(
            'customvariablestatus'  => true,
            'objects'               => true
        );
    }

    /**
     * Join instances
     */
    protected function joinInstances()
    {
        $this->select->join(
            array('i' => $this->prefix . 'instances'),
            'i.instance_id = cvs.instance_id',
            array()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        $group = parent::getGroup();
        if (! empty($group) && $this->ds->getDbType() === 'pgsql') {
            foreach ($this->columnMap as $table => $columns) {
                $pk = ($table === 'objects' ? 'cvo.' : 'cvs.') . $this->getPrimaryKeyColumn($table);
                foreach ($columns as $alias => $_) {
                    if (! in_array($pk, $group, true) && in_array($alias, $group, true)) {
                        $group[] = $pk;
                        break;
                    }
                }
            }
        }

        return $group;
    }
}
