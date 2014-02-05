<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Controller as MonitoringController;
use Icinga\Module\Monitoring\DataView\StatusSummary;
use Icinga\Chart\PieChart;

class Monitoring_TacticalController extends MonitoringController
{
    public function indexAction()
    {
        $this->view->statusSummary = StatusSummary::fromRequest($this->_request)->getQuery()->fetchRow();
    }
}
// @codingStandardsIgnoreStop