<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;

class UnhandledHostMenuItemRenderer extends MonitoringMenuItemRenderer
{
    protected $columns = array(
        'hosts_down_unhandled',
    );
}
