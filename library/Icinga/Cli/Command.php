<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Cli;

use Icinga\Cli\Screen;
use Icinga\Util\Translator;
use Icinga\Cli\Params;
use Icinga\Application\Config;
use Icinga\Application\ApplicationBootstrap as App;
use Exception;
use Icinga\Exception\IcingaException;

abstract class Command
{
    protected $app;
    protected $docs;

    /**
     * @var Params
     */
    protected $params;
    protected $screen;
    protected $isVerbose;
    protected $isDebugging;

    protected $moduleName;
    protected $commandName;
    protected $actionName;

    private $config;

    private $configs;

    protected $defaultActionName = 'default';

    public function __construct(App $app, $moduleName, $commandName, $actionName, $initialize = true)
    {
        $this->app = $app;
        $this->moduleName  = $moduleName;
        $this->commandName = $commandName;
        $this->actionName  = $actionName;
        $this->params     = $app->getParams();
        $this->screen     = Screen::instance($app);
        $this->trace      = $this->params->shift('trace', false);
        $this->isVerbose  = $this->params->shift('verbose', false);
        $this->isDebuging = $this->params->shift('debug', false);
        if ($initialize) {
            $this->init();
        }
    }

    public function Config($file = null)
    {
        if ($this->isModule()) {
            return $this->getModuleConfig($file);
        } else {
            return $this->getMainConfig($file);
        }
    }

    private function getModuleConfig($file = null)
    {
        if ($file === null) {
            if ($this->config === null) {
                $this->config = Config::module($this->moduleName);
            }
            return $this->config;
        } else {
            if (! array_key_exists($file, $this->configs)) {
                $this->configs[$file] = Config::module($this->moduleName, $file);
            }
            return $this->configs[$file];
        }
    }

    private function getMainConfig($file = null)
    {
        if ($file === null) {
            if ($this->config === null) {
                $this->config = Config::app();
            }
            return $this->config;
        } else {
            if (! array_key_exists($file, $this->configs)) {
                $this->configs[$file] = Config::module($module, $file);
            }
            return $this->configs[$file];
        }
        return $this->config;
    }

    public function isModule()
    {
        return substr(get_class($this), 0, 14) === 'Icinga\\Module\\';
    }

    public function setParams(Params $params)
    {
        $this->params = $params;
    }

    public function hasRemainingParams()
    {
        return $this->params->count() > 0;
    }

    public function showTrace()
    {
        return $this->trace;
    }

    /**
     * Translate a string
     *
     * Autoselects the module domain, if any, and falls back to the global one if no translation could be found.
     *
     * @param   string  $text   The string to translate
     *
     * @return  string          The translated string
     */
    public function translate($text)
    {
        $domain = $this->moduleName === null ? 'icinga' : $this->moduleName;
        return Translator::translate($text, $domain);
    }

    public function fail($msg)
    {
        throw new IcingaException($msg);
    }

    public function getDefaultActionName()
    {
        return $this->defaultActionName;
    }

    public function hasDefaultActionName()
    {
        return $this->hasActionName($this->defaultActionName);
    }

    public function hasActionName($name)
    {
        $actions = $this->listActions();
        return in_array($name, $actions);
    }

    public function listActions()
    {
        $actions = array();
        foreach (get_class_methods($this) as $method) {
            if (preg_match('~^([A-Za-z0-9]+)Action$~', $method, $m)) {
                $actions[] = $m[1];
            }
        }
        sort($actions);
        return $actions;
    }

    public function docs()
    {
        if ($this->docs === null) {
            $this->docs = new Documentation($this->app);
        }
        return $this->docs;
    }

    public function showUsage($action = null)
    {
        if ($action === null) {
            $action = $this->actionName;
        }
        echo $this->docs()->usage(
            $this->moduleName,
            $this->commandName,
            $action
        );
    }

    public function init()
    {
    }
}
