<?php

namespace Icinga\Backend\Ido;
class HostgroupsummaryQuery extends GroupsummaryQuery
{
    protected $name_alias       = 'hostgroup_name';
    protected $sub_group_column = 'hg.alias';

    protected function addSummaryJoins($query)
    {
        $this->joinServiceHostgroups($query);
    }
}

