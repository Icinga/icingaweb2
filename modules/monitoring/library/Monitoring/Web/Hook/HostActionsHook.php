<?php

namespace Icinga\Module\Monitoring\Web\Hook;

use Icinga\Module\Monitoring\Object\Host;

abstract class HostActionsHook
{
    abstract public function getActionsForHost(Host $host);
}
