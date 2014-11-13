<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use ErrorException;
use Exception;
use Icinga\Application\Modules\Manager as ModuleManager;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Icinga\Application\Logger;
use Icinga\Util\DateTimeFactory;
use Icinga\Util\Translator;
use Icinga\Util\TimezoneDetect;
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
     * Base directory
     *
     * Parent folder for at least application, bin, modules, library/vendor and public
     *
     * @var string
     */
    protected $baseDir;

    /**
     * Application directory
     *
     * @var string
     */
    protected $appDir;

    /**
     * Vendor library directory
     *
     * @var string
     */
    protected $vendorDir;

    /**
     * Library directory
     *
     * @var string
     */
    protected $libDir;

    /**
     * Configuration directory
     *
     * @var string
     */
    protected $configDir;

    /**
     * Icinga auto loader
     *
     * @var Loader
     */
    private $loader;

    /**
     * Config object
     *
     * @var Config
     */
    protected $config;

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
     * @param string $baseDir   Icinga Web 2 base directory
     * @param string $configDir Path to Icinga Web 2's configuration files
     */
    protected function __construct($baseDir = null, $configDir = null)
    {
        if ($baseDir === null) {
            $baseDir = dirname($this->getBootstrapDirectory());
        }
        $this->baseDir = $baseDir;
        $this->appDir = $baseDir . '/application';
        $this->vendorDir = $baseDir . '/library/vendor';
        $this->libDir = realpath(__DIR__ . '/../..');

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
     * Get the base directory
     *
     * @param   string $subDir Optional sub directory to get
     *
     * @return  string
     */
    public function getBaseDir($subDir = null)
    {
        return $this->getDirWithSubDir($this->baseDir, $subDir);
    }

    /**
     * Get the application directory
     *
     * @param   string $subDir Optional sub directory to get
     *
     * @return  string
     */
    public function getApplicationDir($subDir = null)
    {
        return $this->getDirWithSubDir($this->appDir, $subDir);
    }

    /**
     * Get the vendor library directory
     *
     * @param   string $subDir Optional sub directory to get
     *
     * @return  string
     */
    public function getVendorDir($subDir = null)
    {
        return $this->getDirWithSubDir($this->vendorDir, $subDir);
    }

    /**
     * Get the configuration directory
     *
     * @param   string $subDir Optional sub directory to get
     *
     * @return  string
     */
    public function getConfigDir($subDir = null)
    {
        return $this->getDirWithSubDir($this->configDir, $subDir);
    }

    /**
     * Get the path to the bootstrapping directory
     *
     * This is usually /public for Web and EmbeddedWeb and /bin for the CLI
     *
     * @return string
     */
    public function getBootstrapDirectory()
    {
        return dirname(realpath($_SERVER['SCRIPT_FILENAME']));
    }

    /**
     * Start the bootstrap
     *
     * @param   string $baseDir     Icinga Web 2 base directory
     * @param   string $configDir   Path to Icinga Web 2's configuration files
     *
     * @return  static
     */
    public static function start($baseDir = null, $configDir = null)
    {
        $application = new static($baseDir, $configDir);
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
            explode(':', $this->config->fromSection('global', 'modulePath', $this->baseDir . '/modules'))
        );
        return $this;
    }

    /**
     * Load all core modules
     *
     * @return self
     */
    protected function loadCoreModules()
    {
        try {
            $this->moduleManager->loadCoreModules();
        } catch (NotReadableError $e) {
            Logger::error(new IcingaException('Cannot load core modules. An exception was thrown:', $e));
        }
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
            new Config(
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
            $this->config = new Config();
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
                case E_WARNING:
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
        if (($loggingConfig = $this->config->logging) !== null) {
            try {
                Logger::create($loggingConfig);
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
     * Detect the timezone
     *
     * @return null|string
     */
    protected function detectTimezone()
    {
        return null;
    }

    /**
     * Set up the timezone
     *
     * @return $this
     */
    protected final function setupTimezone()
    {
        $timezone = $this->detectTimeZone();
        if ($timezone === null || @date_default_timezone_set($timezone) === false) {
            $timezone = @date_default_timezone_get();
            if ($timezone === false) {
                $timezone = 'UTC';
                date_default_timezone_set($timezone);
            }
        }
        DateTimeFactory::setConfig(array('timezone' => $timezone));
        return $this;
    }

    /**
     * Detect the locale
     *
     * @return null|string
     */
    protected function detectLocale()
    {
        return null;
    }

    /**
     * Set up internationalization using gettext
     *
     * @return $this
     */
    protected final function setupInternationalization()
    {
        if ($this->hasLocales()) {
            Translator::registerDomain(Translator::DEFAULT_DOMAIN, $this->getLocaleDir());
        }

        $locale = $this->detectLocale();
        if ($locale === null) {
            $locale = Translator::DEFAULT_LOCALE;
        }

        try {
            Translator::setupLocale($locale);
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
