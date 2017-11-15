<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Clicommands;

use Icinga\Cli\Command;
use Icinga\Module\Setup\Hook\RequirementsHook;

class RequirementsCommand extends Command
{
    /**
     * Checks if requirements for Icinga Web 2 to run are fulfilled on the system
     *
     * Including enabled modules
     *
     * Warning: This will check the settings on CLI, results are not necessarily valid for the web environment!
     *
     * USAGE:
     *
     *  icingacli setup requirements check [--quiet]
     */
    public function checkAction()
    {
        $set = RequirementsHook::allRequirements();

        if (! $this->params->get('quiet', false)) {
            echo $set->toText();
        }

        if (! $set->fulfilled()) {
            exit(1);
        }
    }
}
