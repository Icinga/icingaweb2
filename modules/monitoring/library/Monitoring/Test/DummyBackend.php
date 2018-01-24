<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Test;

use Icinga\Data\ConfigObject;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;

class DummyBackend extends MonitoringBackend
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($name = 'dummy', ConfigObject $config = null)
    {
        $this->name = $name;
        $this->config = $config;
    }
}
