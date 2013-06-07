<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Backend\Statusdat;

/**
 * Class HostgroupsummaryQuery
 * @package Icinga\Backend\Statusdat
 */
class HostgroupsummaryQuery extends GroupsummaryQuery
{
    /**
     * @var string
     */
    protected $groupType = "hostgroup";

    /**
     * @var string
     */
    protected $base     = "hosts";
}
