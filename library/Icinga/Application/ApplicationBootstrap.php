<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

use ErrorException;
use Exception;
use LogicException;
use Icinga\Application\Modules\Manager as ModuleManager;
use Icinga\Data\ConfigObject;
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
     * Whether Icinga Web 2 requires setup
     *
     * @var bool
     */
    protected $requiresSetup = false;

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
                $configDir = '/etc/icingaweb2';
            }
        }
        $canonical = realpath($configDir);
        $this->configDir = $canonical ? $canonical : $configDir;

        $this->setupAutoloader();

        set_include_path(
            implode(
                PATH_SEPARATOR,
                array($this->vendorDir, get_include_path())
            )
        );

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
     * Get the Icinga library directory
     *
     * @param   string $subDir Optional sub directory to get
     *
     * @return  string
     */
    public function getLibraryDir($subDir = null)
    {
        return $this->getDirWithSubDir($this->libDir, $subDir);
    }

    /**
     * Get the path to the bootstrapping directory
     *
     * This is usually /public for Web and EmbeddedWeb and /bin for the CLI
     *
     * @return  string
     *
     * @throws  LogicException If the base directory can not be detected
     */
    public function getBootstrapDirectory()
    {
        $script = $_SERVER['SCRIPT_FILENAME'];
        $canonical = realpath($script);
        if ($canonical !== false) {
            $dir = dirname($canonical);
        } elseif (substr($script, -14) === '/webrouter.php') {
            // If Icinga Web 2 is served using PHP's built-in webserver with our webrouter.php script, the $_SERVER
            // variable SCRIPT_FILENAME is set to DOCUMENT_ROOT/webrouter.php which is not a valid path to
            // realpath but DOCUMENT_ROOT here still is the bootstrapping directory
            $dir = dirname($script);
        } else {
            throw new LogicException('Can\'t detected base directory');
        }
        return $dir;
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
     * @return $this
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
     * @return $this
     */
    public function setupZendAutoloader()
    {
        require_once 'Zend/Loader/Autoloader.php';

        \Zend_Loader_Autoloader::getInstance();

        \Zend_Paginator::addScrollingStylePrefixPath(
            'Icinga_Web_Paginator_ScrollingStyle_', $this->libDir . '/Icinga/Web/Paginator/ScrollingStyle'
        );

        return $this;
    }
    /**
     * Setup module manager
     *
     * @return $this
     */
    protected function setupModuleManager()
    {
        $this->moduleManager = new ModuleManager(
            $this,
            $this->configDir . '/enabledModules',
            explode(':', $this->config->get('global', 'module_path', $this->baseDir . '/modules'))
        );
        return $this;
    }

    /**
     * Load all enabled modules
     *
     * @return $this
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
     * Load the setup module if Icinga Web 2 requires setup or the setup token exists
     *
     * @return $this
     */
    protected function loadSetupModuleIfNecessary()
    {
        if (! @file_exists($this->config->resolvePath('authentication.ini'))) {
            $this->requiresSetup = true;
            $this->moduleManager->loadModule('setup');
        } elseif ($this->setupTokenExists()) {
            // Load setup module but do not require setup
            $this->moduleManager->loadModule('setup');
        }
        return $this;
    }

    /**
     * Get whether Icinga Web 2 requires setup
     *
     * @return bool
     */
    public function requiresSetup()
    {
        return $this->requiresSetup;
    }

    /**
     * Get whether the setup token exists
     *
     * @return bool
     */
    public function setupTokenExists()
    {
        return @file_exists($this->config->resolvePath('setup.token'));
    }

    /**
     * Setup default logging
     *
     * @return $this
     */
    protected function setupLogging()
    {
        Logger::create(
            new ConfigObject(
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
     * @return $this
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
     * @return $this
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
                case E_NOTICE:
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
     * @return $this
     */
    protected function setupLogger()
    {
        if ($this->config->hasSection('logging')) {
            $loggingConfig = $this->config->getSection('logging');

            try {
                Logger::create($loggingConfig);
            } catch (ConfigurationError $e) {
                Logger::getInstance()->registerConfigError($e->getMessage());

                try {
                    Logger::getInstance()->setLevel($loggingConfig->get('level', Logger::ERROR));
                } catch (ConfigurationError $e) {
                    Logger::getInstance()->registerConfigError($e->getMessage());
                }
            }
        }

        return $this;
    }

    /**
     * Set up the resource factory
     *
     * @return $this
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
