<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class InstanceQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'instances' => array(
            'instance_id'   => 'i.instance_id',
            'instance_name' => 'i.instance_name'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select()->from(array('i' => $this->prefix . 'instances'), array());
        $this->joinedVirtualTables['instances'] = true;
    }
}
