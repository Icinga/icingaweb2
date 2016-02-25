<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Cli;

use Icinga\Application\ApplicationBootstrap as App;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\ProgrammingError;
use Icinga\Cli\Params;
use Icinga\Cli\Screen;
use Icinga\Cli\Command;
use Icinga\Cli\Documentation;
use Exception;

/**
 *
 */
class Loader
{
    protected $app;

    protected $docs;

    protected $commands;

    protected $modules;

    protected $moduleCommands = array();

    protected $coreAppDir;

    protected $screen;

    protected $moduleName;

    protected $commandName;

    protected $actionName; // Should this better be moved to the Command?

    /**
     * [$command] = $class;
     */
    protected $commandClassMap = array();

    /**
     * [$command] = $file;
     */
    protected $commandFileMap = array();

    /**
     * [$module][$command] = $class;
     */
    protected $moduleClassMap = array();

    /**
     * [$module][$command] = $file;
     */
    protected $moduleFileMap = array();

    protected $commandInstances = array();

    protected $moduleInstances = array();

    protected $lastSuggestions = array();

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->coreAppDir = $app->getApplicationDir('clicommands');
    }

    /**
     * Screen shortcut
     *
     * @return Screen
     */
    protected function screen()
    {
        if ($this->screen === null) {
            $this->screen = Screen::instance();
        }
        return $this->screen;
    }

    /**
     * Documentation shortcut
     *
     * @return Documentation
     */
    protected function docs()
    {
        if ($this->docs === null) {
            $this->docs = new Documentation($this->app);
        }
        return $this->docs;
    }

    /**
     * Show given message and exit
     *
     * @param  string $msg message to show
     */
    public function fail($msg)
    {
        printf("%s: %s\n", $this->screen()->colorize('ERROR', 'red'), $msg);
        exit(1);
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function setModuleName($name)
    {
        $this->moduleName = $name;
        return $this;
    }

    public function getCommandName()
    {
        return $this->commandName;
    }

    public function getActionName()
    {
        return $this->actionName;
    }

    public function getCommandInstance($command)
    {
        if (! array_key_exists($command, $this->commandInstances)) {
            $this->assertCommandExists($command);
            require_once $this->commandFileMap[$command];
            $className = $this->commandClassMap[$command];
            $this->commandInstances[$command] = new $className(
                $this->app,
                null,
                $command,
                null,
                false
            );
        }
        return $this->commandInstances[$command];
    }

    public function getModuleCommandInstance($module, $command)
    {
        if (! array_key_exists($command, $this->moduleInstances[$module])) {
            $this->assertModuleCommandExists($module, $command);
            require_once $this->moduleFileMap[$module][$command];
            $className = $this->moduleClassMap[$module][$command];
            $this->moduleInstances[$module][$command] = new $className(
                $this->app,
                $module,
                $command,
                null,
                false
            );
        }
        return $this->moduleInstances[$module][$command];
    }

    public function getLastSuggestions()
    {
        return $this->lastSuggestions;
    }

    public function showLastSuggestions()
    {
        if (! empty($this->lastSuggestions)) {
            foreach ($this->lastSuggestions as & $s) {
                $s = $this->screen()->colorize($s, 'lightblue');
            }
            printf(
                "Did you mean %s?\n",
                implode(" or ", $this->lastSuggestions)
            );
        }
    }

    public function parseParams(Params $params = null)
    {
        if ($params === null) {
            $params = $this->app->getParams();
        }

        if ($this->moduleName === null) {
            $first = $params->shift();
            if (! $first) {
                return;
            }
            $found = $this->resolveName($first);
        } else {
            $found = $this->moduleName;
        }
        if (! $found) {
            $msg = "There is no such module or command: '$first'";
            printf("%s: %s\n", $this->screen()->colorize('ERROR', 'red'), $msg);
            $this->showLastSuggestions();
            echo "\n";
        }

        $obj = null;
        if ($this->hasCommand($found)) {
            $this->commandName = $found;
            $obj = $this->getCommandInstance($this->commandName);
        } elseif ($this->hasModule($found)) {
            $this->moduleName = $found;
            $command = $this->resolveModuleCommandName($found, $params->shift());
            if ($command) {
                $this->commandName = $command;
                $obj = $this->getModuleCommandInstance(
                    $this->moduleName,
                    $this->commandName
                );
            }
        }
        if ($obj !== null) {
            $action = $this->resolveObjectActionName(
                $obj,
                $params->getStandalone()
            );
            if ($obj->hasActionName($action)) {
                $this->actionName = $action;
                $params->shift();
            } elseif ($obj->hasDefaultActionName()) {
                $this->actionName = $obj->getDefaultActionName();
            }
        }
        return $this;
    }

    public function handleParams(Params $params = null)
    {
        $this->parseParams($params);
        $this->dispatch();
    }

    public function dispatch(Params $overrideParams = null)
    {
        if ($this->commandName === null) {
            echo $this->docs()->usage($this->moduleName);
            return false;
        } elseif ($this->actionName === null) {
            echo $this->docs()->usage($this->moduleName, $this->commandName);
            return false;
        }

        try {
            if ($this->moduleName) {
                $this->app->getModuleManager()->loadModule($this->moduleName);
                $obj = $this->getModuleCommandInstance(
                    $this->moduleName,
                    $this->commandName
                );
            } else {
                $obj = $this->getCommandInstance($this->commandName);
            }
            if ($overrideParams !== null) {
                $obj->setParams($overrideParams);
            }
            $obj->init();
            return $obj->{$this->actionName . 'Action'}();
        } catch (Exception $e) {
            if ($obj && $obj instanceof Command && $obj->showTrace()) {
                echo $this->formatTrace($e->getTrace());
            }

            $this->fail(IcingaException::describe($e));
        }
    }

    protected function searchMatch($needle, $haystack)
    {
        $this->lastSuggestions = preg_grep(sprintf('/^%s.*$/', preg_quote($needle, '/')), $haystack);
        $match = array_search($needle, $haystack, true);
        if (false !== $match) {
            return $haystack[$match];
        }
        if (count($this->lastSuggestions) === 1) {
            $lastSuggestions = array_values($this->lastSuggestions);
            return $lastSuggestions[0];
        }
        return false;
    }

    public function resolveName($name)
    {
        return $this->searchMatch(
            $name,
            array_merge($this->listCommands(), $this->listModules())
        );
    }

    public function resolveCommandName($name)
    {
        return $this->searchMatch($name, $this->listCommands());
    }

    public function resolveModuleName($name)
    {
        return $this->searchMatch($name, $this->listModules());
    }

    public function resolveModuleCommandName($module, $name)
    {
        return $this->searchMatch($name, $this->listModuleCommands($module));
    }

    public function resolveObjectActionName($obj, $name)
    {
        return $this->searchMatch($name, $obj->listActions());
    }

    protected function assertModuleExists($module)
    {
        if (! $this->hasModule($module)) {
            throw new ProgrammingError(
                'There is no such module: %s',
                $module
            );
        }
    }

    protected function assertCommandExists($command)
    {
        if (! $this->hasCommand($command)) {
            throw new ProgrammingError(
                'There is no such command: %s',
                $command
            );
        }
    }

    protected function assertModuleCommandExists($module, $command)
    {
        $this->assertModuleExists($module);
        if (! $this->hasModuleCommand($module, $command)) {
            throw new ProgrammingError(
                'The module \'%s\' has no such command: %s',
                $module,
                $command
            );
        }
    }

    protected function formatTrace($trace)
    {
        $output = array();
        foreach ($trace as $i => $step) {
            $object = '';
            if (isset($step['object']) && is_object($step['object'])) {
                $object = sprintf('[%s]', get_class($step['object'])) . $step['type'];
            } elseif (! empty($step['object'])) {
                $object = (string) $step['object'] . $step['type'];
            }
            if (is_array($step['args'])) {
                foreach ($step['args'] as & $arg) {
                    if (is_object($arg)) {
                        $arg = sprintf('[%s]', get_class($arg));
                    }
                    if (is_string($arg)) {
                        $arg = preg_replace('~\n~', '\n', $arg);
                        if (strlen($arg) > 50) {
                            $arg = substr($arg, 0, 47) . '...';
                        }
                        $arg = "'" . $arg . "'";
                    }
                    if ($arg === null) {
                        $arg = 'NULL';
                    }
                    if (is_bool($arg)) {
                        $arg = $arg ? 'TRUE' : 'FALSE';
                    }
                }
            } else {
                $step['args'] = array();
            }
            $args = $step['args'];
            foreach ($args as & $v) {
                if (is_array($v)) {
                    $v = var_export($v, 1);
                } else {
                    $v = (string) $v;
                }
            }
            $output[$i] = sprintf(
                '#%d %s:%d %s%s(%s)',
                $i,
                isset($step['file']) ? preg_replace(
                    '~.+/library/~',
                    'library/',
                    $step['file']
                ) : '[unknown file]',
                isset($step['line']) ? $step['line'] : '0',
                $object,
                $step['function'],
                implode(', ', $args)
            );
        }
        return implode(PHP_EOL, $output) . PHP_EOL;
    }

    public function hasCommand($name)
    {
        return in_array($name, $this->listCommands());
    }

    public function hasModule($name)
    {
        return in_array($name, $this->listModules());
    }

    public function hasModuleCommand($module, $name)
    {
        return in_array($name, $this->listModuleCommands($module));
    }

    public function listModules()
    {
        if ($this->modules === null) {
            $this->modules = array();
            try {
                $this->modules = array_unique(array_merge(
                    $this->app->getModuleManager()->listEnabledModules(),
                    $this->app->getModuleManager()->listLoadedModules()
                ));
            } catch (NotReadableError $e) {
                $this->fail($e->getMessage());
            }
        }
        return $this->modules;
    }

    protected function retrieveCommandsFromDir($dirname)
    {
        $commands = array();
        if (! @file_exists($dirname) || ! is_readable($dirname)) {
            return $commands;
        }

        $base = opendir($dirname);
        if ($base === false) {
            return $commands;
        }
        while (false !== ($dir = readdir($base))) {
            if ($dir[0] === '.') {
                continue;
            }
            if (preg_match('~^([A-Za-z0-9]+)Command\.php$~', $dir, $m)) {
                $cmd = strtolower($m[1]);
                $commands[] = $cmd;
            }
        }
        sort($commands);
        return $commands;
    }

    public function listCommands()
    {
        if ($this->commands === null) {
            $this->commands = array();
            $ns = 'Icinga\\Clicommands\\';
            $this->commands = $this->retrieveCommandsFromDir($this->coreAppDir);
            foreach ($this->commands as $cmd) {
                $this->commandClassMap[$cmd] = $ns . ucfirst($cmd) . 'Command';
                $this->commandFileMap[$cmd] = $this->coreAppDir . '/' . ucfirst($cmd) . 'Command.php';
            }
        }
        return $this->commands;
    }

    public function listModuleCommands($module)
    {
        if (! array_key_exists($module, $this->moduleCommands)) {
            $ns = 'Icinga\\Module\\' . ucfirst($module) . '\\Clicommands\\';
            $this->assertModuleExists($module);
            $manager = $this->app->getModuleManager();
            $manager->loadModule($module);
            $dir = $manager->getModuleDir($module) . '/application/clicommands';
            $this->moduleCommands[$module] = $this->retrieveCommandsFromDir($dir);
            $this->moduleInstances[$module] = array();
            foreach ($this->moduleCommands[$module] as $cmd) {
                $this->moduleClassMap[$module][$cmd] = $ns . ucfirst($cmd) . 'Command';
                $this->moduleFileMap[$module][$cmd] = $dir . '/' . ucfirst($cmd) . 'Command.php';
            }
        }
        return $this->moduleCommands[$module];
    }
}
