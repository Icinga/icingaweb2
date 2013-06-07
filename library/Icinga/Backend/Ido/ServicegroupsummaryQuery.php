<?php

namespace Icinga\Backend\Ido;
class ServicegroupsummaryQuery extends GroupsummaryQuery
{
    protected $name_alias = 'servicegroup_name';
    protected $sub_group_column = 'sg.alias';

    protected function addSummaryJoins($query)
    {
        $this->joinServicegroups($query);
    }
}

