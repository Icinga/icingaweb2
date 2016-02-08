<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Clicommands;

use Icinga\Cli\Command;
use Icinga\Cli\Loader;
use Icinga\Cli\Documentation;

/**
 * Help for modules, commands and actions
 *
 * The help command shows help for a given command, module and also for a
 * given module's command or a specific command's action.
 *
 * Usage: icingacli help [<module>] [<command> [<action>]]
 */
class HelpCommand extends Command
{
    protected $defaultActionName = 'show';

    /**
     * Show help for modules, commands and actions [default]
     *
     * The help command shows help for a given command, module and also for a
     * given module's command or a specific command's action.
     *
     * Usage: icingacli help [<module>] [<command> [<action>]]
     */
    public function showAction()
    {
        $module  = null;
        $command = null;
        $action  = null;
        $loader = new Loader($this->app);
        $loader->parseParams();
        echo $this->docs()->usage(
            $loader->getModuleName(),
            $loader->getCommandName(),
            $loader->getActionName()
        );
    }
}
