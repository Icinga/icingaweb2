<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 * 
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Icinga\Application\Modules\Manager as ModuleManager;
use Icinga\Application\Platform;
use Icinga\Exception\ProgrammingError;
use Icinga\Config\Config;
use Zend_Loader_Autoloader;
use Icinga\Exception\ConfigurationError;

/**
 * This class bootstraps a thin Icinga application layer
 *
 * Usage example for CLI:
 * <code>
 * use Icinga\Application\Cli;

 * Cli::start();
 * </code>
 *
 * Usage example for Icinga Web application:
 * <code>
 * use Icinga\Application\Web;
 * Web::start()->dispatch();
 * </code>
 *
 * Usage example for Icinga-Web 1.x compatibility mode:
 * <code>
 * use Icinga\Application\LegacyWeb;
 * LegacyWeb::start()->setIcingaWebBasedir(ICINGAWEB_BASEDIR)->dispatch();
 * </code>
 */
abstract class ApplicationBootstrap
{
    /**
     * Icinga auto loader
     *
     * @var Loader
     */
    private $loader;

    /**
     * Library directory
     *
     * @var string
     */
    private $libDir;

    /**
     * Config object
     *
     * @var Config
     */
    private $config;

    /**
     * Configuration directory
     *
     * @var string
     */
    private $configDir;

    /**
     * Application directory
     *
     * @var string
     */
    private $appDir;

    /**
     * Module manager
     *
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * Flag indicates we're on cli environment
     *
     * @var bool
     */
    protected $isCli = false;

    /**
     * Flag indicates we're on web environment
     * 
     * @var bool
     */
    protected $isWeb = false;

    /**
     * Constructor
     *
     * The constructor is protected to avoid incorrect usage
     */
    protected function __construct($configDir)
    {
        $this->libDir = realpath(__DIR__. '/../..');

        if (!defined('ICINGA_LIBDIR')) {
            define('ICINGA_LIBDIR', $this->libDir);
        }

        // TODO: Make appdir configurable for packagers
        $this->appDir = realpath($this->libDir. '/../application');

        if (!defined('ICINGA_APPDIR')) {
            define('ICINGA_APPDIR', $this->appDir);
        }

        $this->setupAutoloader();
        $this->setupZendAutoloader();

        Benchmark::measure('Bootstrap, autoloader registered');

        Icinga::setApp($this);
        $this->configDir = realpath($configDir);

        require_once dirname(__FILE__) . '/functions.php';
    }

    /**
     * Bootstrap interface method for concrete bootstrap objects
     *
     * @return mixed
     */
    abstract protected function bootstrap();

    /**
     * Getter for module manager
     *
     * @return ModuleManager
     */
    public function getModuleManager()
    {
        return $this->moduleManager;
    }

    /**
     * Getter for class loader
     *
     * @return Loader
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Getter for configuration object
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Flag indicates we're on cli environment
     *
     * @return bool
     */
    public function isCli()
    {
        return $this->isCli;
    }

    /**
     * Flag indicates we're on web environment
     *
     * @return bool
     */
    public function isWeb()
    {
        return $this->isWeb;
    }

    /**
     * Getter for application dir
     *
     * Optional append sub directory
     *
     * @param  null|string $subdir optional subdir
     * @return string
     */
    public function getApplicationDir($subdir = null)
    {
        return $this->getDirWithSubDir($this->appDir, $subdir);
    }

    /**
     * Getter for config dir
     * @param  string|null $subdir
     * @return string
     */
    public function getConfigDir($subdir = null)
    {
        return $this->getDirWithSubDir($this->configDir, $subdir);
    }

    /**
     * Helper to glue directories together
     *
     * @param  string      $dir
     * @param  string|null $subdir
     * @return string
     */
    private function getDirWithSubDir($dir, $subdir=null)
    {
        if ($subdir !== null) {
            $dir .= '/' . ltrim($subdir, '/');
        }

        return $dir;
    }

    /**
     * Starting concrete bootstrap classes
     *
     * @param  string $configDir
     * @return ApplicationBootstrap
     */
    public static function start($configDir)
    {
        $class = get_called_class();
        /** @var ApplicationBootstrap $obj */
        $obj = new $class($configDir);
        $obj->bootstrap();
        return $obj;
    }

    /**
     * Setup icinga auto loader
     *
     * @return self
     */
    public function setupAutoloader()
    {
        require $this->libDir. '/Icinga/Exception/ProgrammingError.php';
        require $this->libDir. '/Icinga/Application/Loader.php';

        $this->loader = new Loader();
        $this->loader->registerNamespace('Icinga', $this->libDir. '/Icinga');
        $this->loader->registerNamespace('Icinga\\Form', $this->appDir. '/forms');
        $this->loader->register();

        return $this;
    }

    /**
     * Register the Zend Autoloader
     *
     * @return self
     */
    protected function setupZendAutoloader()
    {
        require_once 'Zend/Loader/Autoloader.php';

        \Zend_Loader_Autoloader::getInstance();

        // Unfortunately this is needed to get the Zend Plugin loader working:
        set_include_path(
            implode(
                PATH_SEPARATOR,
                array($this->libDir, get_include_path())
            )
        );

        return $this;
    }

    /**
     * Setup module loader and all enabled modules
     *
     * @return self
     */
    protected function setupModules()
    {
        $this->moduleManager = new ModuleManager($this, $this->configDir . '/enabledModules');

        $this->moduleManager->loadEnabledModules();

        return $this;
    }

    /**
     * Load Configuration
     *
     * @return self
     */
    protected function setupConfig()
    {
        Config::$configDir = $this->configDir;
        $this->config = Config::app();
        return $this;
    }

    /**
     * Error handling configuration
     *
     * @return self
     */
    protected function setupErrorHandling()
    {
        if ($this->config->get('global', 'environment') == 'development') {
            error_reporting(E_ALL | E_NOTICE);
            ini_set('display_startup_errors', 1);
            ini_set('display_errors', 1);
        }
        Logger::create($this->config->logging);
        return $this;
    }

    /**
     * Setup default timezone
     *
     * @return self
     */
    protected function setupTimezone()
    {
        date_default_timezone_set(
            $this->config->global->get('timezone', 'UTC')
        );

        return $this;
    }
}
