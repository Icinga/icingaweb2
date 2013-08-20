<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class CustomvarQuery extends AbstractQuery
{
    protected $object_id = 'object_id';

    protected $columnMap = array(
        'customvars' => array(
            'varname'  => 'cvs.varname',
            'varvalue' => 'cvs.varvalue',
        ),
        'objects' => array(
            'host_name'           => 'cvo.name1 COLLATE latin1_general_ci',
            'service_host_name'   => 'cvo.name1 COLLATE latin1_general_ci',
            'service_description' => 'cvo.name2 COLLATE latin1_general_ci',
            'contact_name'        => 'cvo.name1 COLLATE latin1_general_ci',
            'object_type'         => "CASE cvo.objecttype_id WHEN 1 THEN 'host' WHEN 2 THEN 'service' WHEN 10 THEN 'contact' ELSE 'invalid' END"
//             'object_type'         => "CASE cvo.objecttype_id WHEN 1 THEN 'host' WHEN 2 THEN 'service' WHEN 3 THEN 'hostgroup' WHEN 4 THEN 'servicegroup' WHEN 5 THEN 'hostescalation' WHEN 6 THEN 'serviceescalation' WHEN 7 THEN 'hostdependency' WHEN 8 THEN 'servicedependency' WHEN 9 THEN 'timeperiod' WHEN 10 THEN 'contact' WHEN 11 THEN 'contactgroup' WHEN 12 THEN 'command' ELSE 'other' END"
        ),
    );

    protected function joinBaseTables()
    {
        $this->baseQuery = $this->db->select()->from(
            array('cvs' => $this->prefix . 'customvariablestatus'),
            array()
        )->join(
            array('cvo' => $this->prefix . 'objects'),
            'cvs.object_id = cvo.' . $this->object_id
          . ' AND cvo.is_active = 1',
            array()
        );

        $this->joinedVirtualTables = array(
            'customvars' => true,
            'objects'    => true
        );
    }
}
