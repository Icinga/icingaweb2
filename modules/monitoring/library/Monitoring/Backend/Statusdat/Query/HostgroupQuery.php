<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

/**
 * Class HostgroupsummaryQuery
 * @package Icinga\Backend\Statusdat
 */
class HostgroupQuery extends StatusdatQuery
{
    public static $mappedParameters = array(
        'hostgroups'      => 'hostgroup_name',
        'hostgroup_name'  => 'hostgroup_name',
        'hostgroup_alias' => 'alias',
        'host'            => 'host.host_name',
        'host_name'       => 'host.host_name'
    );

    public function selectBase()
    {
        $this->select()->from("hostgroups", array());
    }
}