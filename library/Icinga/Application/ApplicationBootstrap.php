<?php

/**
 * Icinga Application Bootstrap class
 *
 * @package Icinga\Application
 */
namespace Icinga\Application;

use Icinga\Application\Modules\Manager as ModuleManager;
use Icinga\Application\Platform;
use Zend_Loader_Autoloader as ZendLoader;
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
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Application
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
abstract class ApplicationBootstrap
{
    protected $loader;
    protected $libdir;
    protected $config;
    protected $configFile;
    protected $appdir;
    protected $moduleManager;
    protected $isCli = false;
    protected $isWeb = false;

    /**
     * Constructor
     *
     * The constructor is protected to avoid incorrect usage
     *
     * @return void
     */
    protected function __construct($configFile = null)
    {
        $this->checkPrerequisites();
        $this->libdir = realpath(dirname(dirname(dirname(__FILE__))));

        require $this->libdir . '/Icinga/Application/Loader.php';

        if (! defined('ICINGA_LIBDIR')) {
            define('ICINGA_LIBDIR', $this->libdir);
        }
        // TODO: Make appdir configurable for packagers
        $this->appdir = realpath(dirname($this->libdir) . '/application');
        if (! defined('ICINGA_APPDIR')) {
            define('ICINGA_APPDIR', $this->appdir);
        }

        $this->loader = Loader::register();
        $this->registerZendAutoloader();

        Benchmark::measure('Bootstrap, autoloader registered');

        Icinga::setApp($this);

        // Unfortunately this is needed to get the Zend Plugin loader working:
        set_include_path(
            implode(
                PATH_SEPARATOR,
                array($this->libdir, get_include_path())
            )
        );

        if ($configFile === null) {
            $configFile = dirname($this->libdir) . '/config/icinga.ini';
        }
        $this->configFile = $configFile;
        require_once dirname(__FILE__) . '/functions.php';
    }

    abstract protected function bootstrap();

    public function moduleManager()
    {
        if ($this->moduleManager === null) {
            $this->moduleManager = new ModuleManager($this, $this->config->global->moduleFolder);
        }
        return $this->moduleManager;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    protected function loadEnabledModules()
    {
        $this->moduleManager()->loadEnabledModules();
        return $this;
    }

    public function isCli()
    {
        return $this->isCli;
    }

    public function isWeb()
    {
        return $this->isWeb;
    }

    public function getApplicationDir($subdir = null)
    {
        $dir = $this->appdir;
        if ($subdir !== null) {
            $dir .= '/' . ltrim($subdir, '/');
        }
        return $dir;
    }

    public function hasModule($name)
    {
        return $this->moduleManager()->hasLoaded($name);
    }

    public function getModule($name)
    {
        return $this->moduleManager()->getModule($name);
    }

    public function loadModule($name)
    {
        return $this->moduleManager()->loadModule($name);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public static function start($config = null)
    {
        $class = get_called_class();
        $obj = new $class();
        $obj->bootstrap();
        return $obj;
    }

    /**
     * Register the Zend Autoloader
     *
     * @return self
     */
    protected function registerZendAutoloader()
    {
        require_once 'Zend/Loader/Autoloader.php';
        ZendLoader::getInstance();
        return $this;
    }

    /**
     * Check whether we have all we need
     *
     * Pretty useless right now as a namespaces class would not work
     * with PHP 5.3
     *
     * @return self
     */
    protected function checkPrerequisites()
    {
        if (version_compare(phpversion(), '5.3.0', '<') === true) {
            die('PHP > 5.3.0 required');
        }
        return $this;
    }

    /**
     * Check whether a given PHP extension is available
     *
     * @return boolean
     */
    protected function hasExtension($name)
    {
        if (!extension_loaded($name)) {
            if (! @ dl($name)) {
                throw new ConfigurationError(
                    sprintf(
                        'The PHP extension %s is not available',
                        $name
                    )
                );
            }
        }
    }

    /**
     * Load Configuration
     *
     * @return self
     */
    protected function loadConfig()
    {
        // TODO: add an absolutely failsafe config loader
        if (! @is_readable($this->configFile)) {
            throw new \Exception('Cannot read config file: ' . $this->configFile);
        }
        $this->config = Config::getInstance($this->configFile);
        return $this;
    }


    /**
     * Configure cache settings
     *
     * TODO: Right now APC is hardcoded, make this configurable
     *
     * @return self
     */
    protected function configureCache()
    {
        // TODO: Provide Zend_Cache_Frontend_File for statusdat
        //$this->cache = \Zend_Cache::factory('Core', 'Apc');
        return $this;
    }

    /**
     * Error handling configuration
     *
     * @return self
     */
    protected function configureErrorHandling()
    {
        if ($this->config->global->environment == 'development') {
            error_reporting(E_ALL | E_NOTICE);
            ini_set('display_startup_errors', 1);
            ini_set('display_errors', 1);
        }
        Logger::create($this->config->logging);
        return $this;
    }

    /**
     * Set timezone settings
     *
     * @return self
     */
    protected function setTimezone()
    {
        date_default_timezone_set(
            $this->config->{'global'}->get('timezone', 'UTC')
        );
        return $this;
    }
}
