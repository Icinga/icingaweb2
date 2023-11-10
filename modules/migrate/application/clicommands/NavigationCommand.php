<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Migrate\Clicommands;

use Icinga\Application\Logger;
use Icinga\Cli\Command;

class NavigationCommand extends Command
{
    /**
     * Deprecated. Use `icingacli icingadb migrate navigation` instead.
     */
    public function indexAction(): void
    {
        Logger::error('Deprecated. Use `icingacli icingadb migrate navigation` instead.');
        exit(1);
    }
}
