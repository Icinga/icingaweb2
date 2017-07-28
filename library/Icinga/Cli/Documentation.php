<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Cli;

use Icinga\Application\ApplicationBootstrap as App;
use Icinga\Cli\Documentation\CommentParser;
use ReflectionClass;
use ReflectionMethod;

class Documentation
{
    protected $icinga;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->loader = $app->cliLoader();
    }

    public function usage($module = null, $command = null, $action = null)
    {
        if ($module) {
            $module = $this->loader->resolveModuleName($module);
            return $this->moduleUsage($module, $command, $action);
        }
        if ($command) {
            $command = $this->loader->resolveCommandName($command);
            return $this->commandUsage($command, $action);
        }
        return $this->globalUsage();
    }

    public function globalUsage()
    {
        $d = "USAGE: icingacli [module] <command> [action] [options]\n\n"
           . "Available commands:\n\n";
        foreach ($this->loader->listCommands() as $command) {
            if ($command !== 'autocomplete') {
                $obj = $this->loader->getCommandInstance($command);
                $d .= sprintf(
                    "  %-14s  %s\n",
                    $command,
                    $this->getClassTitle($obj)
                );
            }
        }
        $d .= "\nAvailable modules:\n\n";
        foreach ($this->loader->listModules() as $module) {
            $d .= '  ' . $module . "\n";
        }
        $d .= "\nGlobal options:\n\n"
            . "  --log [t]       Log to <t>, either stderr, file or syslog (default: stderr)\n"
            . "  --log-path <f>  Which file to log into in case of --log file\n"
            . "  --verbose       Be verbose\n"
            . "  --debug         Show debug output\n"
            . "  --help          Show help\n"
            . "  --benchmark     Show benchmark summary\n"
            . "  --watch [s]     Refresh output every <s> seconds (default: 5)\n"
            ;
        $d .= "\nShow help on a specific command : icingacli help <command>"
            . "\nShow help on a specific module  : icingacli help <module>"
            . "\n";
        return $d;
    }

    public function moduleUsage($module, $command = null, $action = null)
    {
        $commands = $this->loader->listModuleCommands($module);

        if (empty($commands)) {
            return "The '$module' module does not provide any CLI commands\n";
        }
        $d = '';
        if ($command) {
            $obj = $this->loader->getModuleCommandInstance($module, $command);
        }
        if ($command === null) {
            $d = "USAGE: icingacli $module <command> [<action>] [options]\n\n"
               . "Available commands:\n\n";
            foreach ($commands as $command) {
                $d .= '  ' . $command . "\n";
            }
            $d .= "\nShow help on a specific command: icingacli help $module <command>\n";
        } elseif ($action === null) {
            $d .= $this->showCommandActions($obj, $command);
        } else {
            $action = $this->loader->resolveObjectActionName($obj, $action);
            $d .= $this->getMethodDocumentation($obj, $action);
        }
        return $d;
    }

    /**
     * @param   Command $command
     * @param   string  $name
     *
     * @return  string
     */
    protected function showCommandActions($command, $name)
    {
        $actions = $command->listActions();
        $d = $this->getClassDocumentation($command)
           . "Available actions:\n\n";
        foreach ($actions as $action) {
            $d .= sprintf(
                "  %-14s  %s\n",
                $action,
                $this->getMethodTitle($command, $action)
            );
        }
        $d .= "\nShow help on a specific action: icingacli help ";
        if ($command->isModule()) {
            $d .= $command->getModuleName() . ' ';
        }
        $d .= "$name <action>\n";
        return $d;
    }

    public function commandUsage($command, $action = null)
    {
        $obj = $this->loader->getCommandInstance($command);
        $action = $this->loader->resolveObjectActionName($obj, $action);

        $d = "\n";
        if ($action) {
            $d .= $this->getMethodDocumentation($obj, $action);
        } else {
            $d .= $this->showCommandActions($obj, $command);
        }
        return $d;
    }

    protected function getClassTitle($class)
    {
        $ref = new ReflectionClass($class);
        $comment = new CommentParser($ref->getDocComment());
        return $comment->getTitle();
    }

    protected function getClassDocumentation($class)
    {
        $ref = new ReflectionClass($class);
        $comment = new CommentParser($ref->getDocComment());
        return $comment->dump();
    }

    protected function getMethodTitle($class, $method)
    {
        $ref = new ReflectionMethod($class, $method . 'Action');
        $comment = new CommentParser($ref->getDocComment());
        return $comment->getTitle();
    }

    protected function getMethodDocumentation($class, $method)
    {
        $ref = new ReflectionMethod($class, $method . 'Action');
        $comment = new CommentParser($ref->getDocComment());
        return $comment->dump();
    }
}
