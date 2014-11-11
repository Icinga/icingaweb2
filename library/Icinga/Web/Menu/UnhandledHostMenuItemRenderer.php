<?php

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;

class UnhandledHostMenuItemRenderer extends MonitoringMenuItemRenderer
{
    protected $columns = array(
        'hosts_down_unhandled',
    );
}
