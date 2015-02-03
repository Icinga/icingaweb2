<?php

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;

class UnhandledServiceMenuItemRenderer extends MonitoringMenuItemRenderer
{
    protected $columns = array(
        'services_critical_unhandled'
    );
}
