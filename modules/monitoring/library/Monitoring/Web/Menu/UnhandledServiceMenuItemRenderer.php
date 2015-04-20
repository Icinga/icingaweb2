<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;

class UnhandledServiceMenuItemRenderer extends MonitoringMenuItemRenderer
{
    protected $columns = array(
        'services_critical_unhandled'
    );
}
