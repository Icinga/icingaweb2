<?php

namespace Icinga\Clicommands;

use Icinga\Cli\Command;
use Icinga\Cli\Documentation;

/**
 * Help for modules, commands and actions
 *
 * The help command shows help for a given command, module and also for a
 * given module's command or a specific command's action.
 *
 * Usage: icingaweb help [<module>] [<command> [<action>]]
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
     * Usage: icingaweb help [<module>] [<command> [<action>]]
     */
    public function showAction()
    {
        $module  = null;
        $command = null;
        $action  = null;
        $loader = $this->app->cliLoader();
        $command = $this->params->shift();
        
        if ($loader->hasCommand($command)) {
            $action = $this->params->shift();
            if (! $loader->getCommandInstance($command)->hasActionName($action)) {
                $action = null;
            }
        } else {
            if ($loader->hasModule($command)) {
                $module = $command;
                $command = $this->params->shift();
                if ($loader->hasModuleCommand($module, $command)) {
                    $action = $this->params->shift();
                    $mod = $loader->getModuleCommandInstance($module, $command);
                    if (! $mod->hasActionName($action)) {
                        $action = null;
                    }
                } else {
                    $command = null;
                }
            } else {
                $command = null;
            }
        }
        echo $this->docs()->usage($module, $command, $action);
    }
}
