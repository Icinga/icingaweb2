<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Clicommands;

use Icinga\Application\Daemon;
use Icinga\Cli\Command;
use Icinga\Data\ConfigObject;
use React\EventLoop\Loop;

class DaemonCommand extends Command
{
    public function runAction()
    {
        // TODO: Decide where to fetch the socket path for the webserver from (ini? which ini? envvar?)
        (new Daemon())
            ->addJob(new Daemon\HttpJob(new ConfigObject()))
            ->run();

        Loop::get()->run();
    }
}
