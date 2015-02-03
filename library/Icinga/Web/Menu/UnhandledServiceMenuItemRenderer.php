<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;

class UnhandledServiceMenuItemRenderer extends MonitoringMenuItemRenderer
{
    protected $columns = array(
        'services_critical_unhandled'
    );
}
