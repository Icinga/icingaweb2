<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Hostgroupsummary extends Groupsummary
{
    public function getFilterColumns()
    {
        return array('hostgroup');
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('hostgroup', 'hostgroup_alias');
    }

    /**
     * {@inheritdoc}
     */
    public static function getQueryName()
    {
        return 'groupsummary';
    }
}
