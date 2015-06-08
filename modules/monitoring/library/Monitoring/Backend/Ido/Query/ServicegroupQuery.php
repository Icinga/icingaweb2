<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class ServicegroupQuery extends IdoQuery
{
    protected $columnMap = array(
        'servicegroups' => array(
            'servicegroup'       => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_name'  => 'sgo.name1',
            'servicegroup_alias' => 'sg.alias COLLATE latin1_general_ci'
        ),
        'services' => array(
            'host'                => 'so.name1 COLLATE latin1_general_ci',
            'host_name'           => 'so.name1',
            'service'             => 'so.name2 COLLATE latin1_general_ci',
            'service_host_name'   => 'so.name1',
            'service_description' => 'so.name2'
        )
    );

    protected function joinBaseTables()
    {
        $this->select->from(
            array('sg' => $this->prefix . 'servicegroups'),
            array()
        )->join(
            array('sgo' => $this->prefix . 'objects'),
            'sg.servicegroup_object_id = sgo.' . $this->object_id
          . ' AND sgo.is_active = 1',
            array()
        );

        $this->joinedVirtualTables = array('servicegroups' => true);
    }

    protected function joinServices()
    {
        $this->select->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.' . $this->servicegroup_id . ' = sg.' . $this->servicegroup_id,
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'sgm.service_object_id = so.' . $this->object_id . ' AND so.is_active = 1',
            array()
        );
    }
}
