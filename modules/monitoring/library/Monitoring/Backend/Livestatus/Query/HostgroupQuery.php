<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Livestatus\Query;

use Icinga\Protocol\Livestatus\Query;

class HostgroupQuery extends Query
{
    protected $table = 'hostgroups';

    protected $available_columns = array(
        'hostgroups'      => 'name',
        'hostgroup_name'  => 'name',
        'hostgroup_alias' => 'alias',
        'host'            => 'members',
        'host_name'       => 'members',
    );
}
