<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Clicommands;

use Icinga\Cli\Command;
use Icinga\Cli\Loader;

/**
 * Autocomplete for modules, commands and actions
 *
 * The autocomplete command shows help for a given command, module and also for a
 * given module's command or a specific command's action.
 *
 * Usage: icingacli autocomplete [<module>] [<command> [<action>]]
 */
class AutocompleteCommand extends Command
{
    protected $defaultActionName = 'complete';

    protected function suggest($suggestions)
    {
        if ($suggestions) {
            $key = array_search('autocomplete', $suggestions);
            if ($key !== false) {
                unset($suggestions[$key]);
            }
            echo implode("\n", $suggestions)
            //. serialize($GLOBALS['argv'])
            . "\n";
        }
    }

    /**
     * Show help for modules, commands and actions [default]
     *
     * The help command shows help for a given command, module and also for a
     * given module's command or a specific command's action.
     *
     * Usage: icingacli autocomplete [<module>] [<command> [<action>]]
     */
    public function completeAction()
    {
        $module  = null;
        $command = null;
        $action  = null;

        $loader = new Loader($this->app);
        $params = $this->params;
        $bare_params = $GLOBALS['argv'];
        $cword = (int) $params->shift('autoindex');

        $search_word = $bare_params[$cword];
        if ($search_word === '--') {
            // TODO: Unfinished, completion missing
            return $this->suggest(array('--verbose', '--help', '--debug'));
        }

        $search = $params->shift();
        if (!$search) {
            return $this->suggest(
                array_merge($loader->listCommands(), $loader->listModules())
            );
        }
        $found = $loader->resolveName($search);
        if ($found) {
            // Do not return suggestions if we are already on the next word:
            if ($bare_params[$cword] === $search) {
                return $this->suggest(array($found));
            }
        } else {
            return $this->suggest($loader->getLastSuggestions());
        }

        $obj = null;
        if ($loader->hasCommand($found)) {
            $command = $found;
            $obj = $loader->getCommandInstance($command);
        } elseif ($loader->hasModule($found)) {
            $module = $found;
            $search = $params->shift();
            if (! $search) {
                return $this->suggest(
                    $loader->listModuleCommands($module)
                );
            }
            $command = $loader->resolveModuleCommandName($found, $search);
            if ($command) {
                // Do not return suggestions if we are already on the next word:
                if ($bare_params[$cword] === $search) {
                    return $this->suggest(array($command));
                }
                $obj = $loader->getModuleCommandInstance(
                    $module,
                    $command
                );
            } else {
                return $this->suggest($loader->getLastSuggestions());
            }
        }

        if ($obj !== null) {
            $search = $params->shift();
            if (! $search) {
                return $this->suggest($obj->listActions());
            }
            $action = $loader->resolveObjectActionName(
                $obj,
                $search
            );
            if ($action) {
                if ($bare_params[$cword] === $search) {
                    return $this->suggest(array($action));
                }
            } else {
                return $this->suggest($loader->getLastSuggestions());
            }
        }
    }
}
