<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Migrate\Clicommands;

use Icinga\Application\Icinga;
use Icinga\Cli\Command;

class NavigationCommand extends Command
{
    /**
     * Migrate local user monitoring navigation items to the Icinga DB Web actions
     *
     * USAGE
     *
     *  icingacli migrate navigation [options]
     *
     * OPTIONS:
     *
     *  --user=<username>  Migrate monitoring navigation items only for
     *                     the given user. (Default *)
     *
     *  --override         Override the existing Icinga DB navigation items
     *
     *  --delete           Remove the legacy files after successfully
     *                     migrated the navigation items.
     */
    public function indexAction(): void
    {
        $user = $this->params->get('user');
        if ($user === null) {
            $this->params->set('user', '*');
        }

        (new ToicingadbCommand(
            Icinga::app(),
            'migrate',
            'toicingadb',
            'navigation'
        ))->navigationAction();
    }
}
