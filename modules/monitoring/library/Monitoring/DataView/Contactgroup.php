<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Contactgroup extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'contactgroup_name',
            'contactgroup_alias',
            'contact_count'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'contactgroup_name' => array(
                'order' => self::SORT_ASC
            ),
            'contactgroup_alias' => array(
                'order' => self::SORT_ASC
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'contactgroup',
            'host', 'host_name', 'host_display_name', 'host_alias',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'instance_name',
            'service', 'service_description', 'service_display_name',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('contactgroup_alias');
    }
}
