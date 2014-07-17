<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

/**
 * Class ServicegroupsummaryQuery
 * @package Icinga\Backend\Statusdat
 */
class ServicegroupsummaryQuery extends GroupsummaryQuery
{
    /**
     * @var string
     */
    protected $groupType = "servicegroup";

    /**
     * @var string
     */
    protected $base     = "services";
}
