<?php

namespace Icinga\Web\Menu;

class ProblemMenuItemRenderer extends MonitoringMenuItemRenderer
{
    protected $columns = array(
        'hosts_down_unhandled',
        'services_critical_unhandled'
    );
}
