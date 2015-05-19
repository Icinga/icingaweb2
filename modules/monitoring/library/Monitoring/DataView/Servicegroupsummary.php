<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Servicegroupsummary extends Groupsummary
{
    public function getFilterColumns()
    {
        return array('servicegroup');
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('servicegroup', 'servicegroup_alias');
    }
}
