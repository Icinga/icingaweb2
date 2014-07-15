<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

/**
 * Class HostgroupsummaryQuery
 * @package Icinga\Backend\Statusdat
 */
class ServicegroupQuery extends StatusdatQuery
{
    public static $mappedParameters = array(
        'servicegroups'      => 'servicegroup_name',
        'servicegroup_name'  => 'servicegroup_name',
        'servicegroup_alias' => 'alias',
        'host'               => 'service.host_name',
        'host_name'          => 'service.host_name',
        'service'            => 'service.service_description',
        'service_description'=> 'service.service_description'

    );

    public function selectBase()
    {
        $this->select()->from("servicegroups", array());
    }
}