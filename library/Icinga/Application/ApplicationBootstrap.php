<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use ErrorException;
use Exception;
use Zend_Config;
use Icinga\Application\Modules\Manager as ModuleManager;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Icinga\Application\Logger;
use Icinga\Util\DateTimeFactory;
use Icinga\Util\Translator;
use Icinga\Exception\IcingaException;

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
     * @var Zend_Config
     */
    protected $config;

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
     */
    protected function __construct($configDir = null)
    {
        $this->libDir = realpath(__DIR__ . '/../..');

        if (!defined('ICINGA_LIBDIR')) {
            define('ICINGA_LIBDIR', $this->libDir);
        }

        if (defined('ICINGAWEB_APPDIR')) {
            $this->appDir = ICINGAWEB_APPDIR;
        } elseif (array_key_exists('ICINGAWEB_APPDIR', $_SERVER)) {
            $this->appDir = $_SERVER['ICINGAWEB_APPDIR'];
        } else {
            $this->appDir = realpath($this->libDir. '/../application');
        }

        if (!defined('ICINGAWEB_APPDIR')) {
            define('ICINGAWEB_APPDIR', $this->appDir);
        }

        if ($configDir === null) {
            if (array_key_exists('ICINGAWEB_CONFIGDIR', $_SERVER)) {
                $configDir = $_SERVER['ICINGAWEB_CONFIGDIR'];
            } else {
                $configDir = '/etc/icingaweb';
            }
        }
        $canonical = realpath($configDir);
        $this->configDir = $canonical ? $canonical : $configDir;

        $this->setupAutoloader();
        $this->setupZendAutoloader();

        Benchmark::measure('Bootstrap, autoloader registered');

        Icinga::setApp($this);

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
     * @param   string $subdir optional subdir
     *
     * @return  string
     */
    public function getApplicationDir($subdir = null)
    {
        return $this->getDirWithSubDir($this->appDir, $subdir);
    }

    /**
     * Getter for config dir
     *
     * @param   string $subdir
     *
     * @return  string
     */
    public function getConfigDir($subdir = null)
    {
        return $this->getDirWithSubDir($this->configDir, $subdir);
    }

    /**
     * Get the path to the bootstrapping directory.
     *
     * This is usually /public for Web and EmbeddedWeb
     *
     * @return string
     */
    public function getBootstrapDirecory()
    {
        return dirname($_SERVER['SCRIPT_FILENAME']);
    }

    /**
     * Helper to glue directories together
     *
     * @param   string $dir
     * @param   string $subdir
     *
     * @return  string
     */
    private function getDirWithSubDir($dir, $subdir = null)
    {
        if ($subdir !== null) {
            $dir .= '/' . ltrim($subdir, '/');
        }

        return $dir;
    }

    /**
     * Starting concrete bootstrap classes
     *
     * @param   string $configDir
     *
     * @return  ApplicationBootstrap
     */
    public static function start($configDir = null)
    {
        $application = new static($configDir);
        $application->bootstrap();
        return $application;
    }

    /**
     * Setup Icinga auto loader
     *
     * @return self
     */
    public function setupAutoloader()
    {
        require $this->libDir . '/Icinga/Application/Loader.php';

        $this->loader = new Loader();
        $this->loader->registerNamespace('Icinga', $this->libDir. '/Icinga');
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
     * Setup module manager
     *
     * @return self
     */
    protected function setupModuleManager()
    {
        $this->moduleManager = new ModuleManager(
            $this,
            $this->configDir . '/enabledModules',
            explode(
                ':',
                $this->config->global !== null
                    ? $this->config->global->get('modulePath', ICINGAWEB_APPDIR . '/../modules')
                    : ICINGAWEB_APPDIR . '/../modules'
            )
        );
        return $this;
    }

    /**
     * Load all enabled modules
     *
     * @return self
     */
    protected function loadEnabledModules()
    {
        try {
            $this->moduleManager->loadEnabledModules();
        } catch (NotReadableError $e) {
            Logger::error(new IcingaException('Cannot load enabled modules. An exception was thrown:', $e));
        }
        return $this;
    }

    /**
     * Setup default logging
     *
     * @return  self
     */
    protected function setupLogging()
    {
        Logger::create(
            new Zend_Config(
                array(
                    'log' => 'syslog'
                )
            )
        );
        return $this;
    }

    /**
     * Load Configuration
     *
     * @return self
     */
    protected function loadConfig()
    {
        Config::$configDir = $this->configDir;
        try {
            $this->config = Config::app();
        } catch (NotReadableError $e) {
            Logger::error(new IcingaException('Cannot load application configuration. An exception was thrown:', $e));
            $this->config = new Zend_Config(array());
        }
        return $this;
    }

    /**
     * Error handling configuration
     *
     * @return self
     */
    protected function setupErrorHandling()
    {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_startup_errors', 1);
        ini_set('display_errors', 1);
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (error_reporting() === 0) {
                // Error was suppressed with the @-operator
                return false; // Continue with the normal error handler
            }
            switch($errno) {
                case E_STRICT:
                    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
            return false; // Continue with the normal error handler
        });
        return $this;
    }

    /**
     * Set up logger
     *
     * @return self
     */
    protected function setupLogger()
    {
        if ($this->config->logging !== null) {
            try {
                Logger::create($this->config->logging);
            } catch (ConfigurationError $e) {
                Logger::error($e);
            }
        }
        return $this;
    }

    /**
     * Set up the resource factory
     *
     * @return self
     */
    protected function setupResourceFactory()
    {
        try {
            $config = Config::app('resources');
            ResourceFactory::setConfig($config);
        } catch (NotReadableError $e) {
            Logger::error(
                new IcingaException('Cannot load resource configuration. An exception was thrown:', $e)
            );
        }

        return $this;
    }

    /**
     * Setup default timezone
     *
     * @return  self
     * @throws  ConfigurationError if the timezone in config.ini isn't valid
     */
    protected function setupTimezone()
    {
        $default = @date_default_timezone_get();
        if (! $default) {
            $default = 'UTC';
        }
        $timeZoneString = $this->config->global !== null ? $this->config->global->get('timezone', $default) : $default;
        date_default_timezone_set($timeZoneString);
        DateTimeFactory::setConfig(array('timezone' => $timeZoneString));
        return $this;
    }

    /**
     * Setup internationalization using gettext
     *
     * Uses the preferred language sent by the browser or the default one
     *
     * @return  self
     */
    protected function setupInternationalization()
    {
        if ($this->hasLocales()) {
            Translator::registerDomain(Translator::DEFAULT_DOMAIN, $this->getLocaleDir());
        }

        try {
            Translator::setupLocale(
                isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
                    ? Translator::getPreferredLocaleCode($_SERVER['HTTP_ACCEPT_LANGUAGE'])
                    : Translator::DEFAULT_LOCALE
            );
        } catch (Exception $error) {
            Logger::error($error);
        }

        return $this;
    }

    /**
     * @return string Our locale directory
     */
    public function getLocaleDir()
    {
        return $this->getApplicationDir('locale');
    }

    /**
     * return bool Whether Icinga Web has translations
     */
    public function hasLocales()
    {
        $localedir = $this->getLocaleDir();
        return file_exists($localedir) && is_dir($localedir);
    }

    /**
     * List all available locales
     *
     * NOTE: Might be a candidate for a static function in Translator
     *
     * return array Locale list
     */
    public function listLocales()
    {
        $locales = array();
        if (! $this->hasLocales()) {
            return $locales;
        }
        $localedir = $this->getLocaleDir();

        $dh = opendir($localedir);
        while (false !== ($file = readdir($dh))) {
            $filename = $localedir . DIRECTORY_SEPARATOR . $file;
            if (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $file) && is_dir($filename)) {
                $locales[] = $file;
            }
        }
        closedir($dh);
        sort($locales);
        return $locales;
    }
}
