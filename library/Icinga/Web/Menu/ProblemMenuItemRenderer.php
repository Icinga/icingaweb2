<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Web\Menu;

class ProblemMenuItemRenderer extends MonitoringMenuItemRenderer
{
    protected $columns = array(
        'hosts_down_unhandled',
        'services_critical_unhandled'
    );
}
