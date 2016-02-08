<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Customvar extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'varname',
            'varvalue',
            'is_json',
            'host_name',
            'service_description',
            'contact_name',
            'object_type',
            'object_type_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'varname' => array(
                'columns' => array(
                    'varname',
                    'varvalue'
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array('host', 'service', 'contact');
    }
}
