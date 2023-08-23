<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

use DirectoryIterator;
use ErrorException;
use Exception;
use ipl\I18n\GettextTranslator;
use ipl\I18n\StaticTranslator;
use LogicException;
use Icinga\Application\Modules\Manager as ModuleManager;
use Icinga\Authentication\User\UserBackend;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
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
     * Parent folder for at least application, bin, modules and public
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
     * Icinga library directory
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
     * Locale directory
     *
     * @var string
     */
    protected $localeDir;

    /**
     * Common storage directory
     *
     * @var string
     */
    protected $storageDir;

    /**
     * External library paths
     *
     * @var string[]
     */
    protected $libraryPaths;

    /**
     * Loaded external libraries
     *
     * @var Libraries
     */
    protected $libraries;

    /**
     * Icinga class loader
     *
     * @var ClassLoader
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
     * @param string $baseDir       Icinga Web 2 base directory
     * @param string $configDir     Path to Icinga Web 2's configuration files
     * @param string $storageDir    Path to Icinga Web 2's stored files
     */
    protected function __construct($baseDir = null, $configDir = null, $storageDir = null)
    {
        if ($baseDir === null) {
            $baseDir = dirname($this->getBootstrapDirectory());
        }
        $this->baseDir = $baseDir;
        $this->appDir = $baseDir . '/application';
        if (substr(__DIR__, 0, 8) === 'phar:///') {
            $this->libDir = dirname(dirname(__DIR__));
        } else {
            $this->libDir = realpath(__DIR__ . '/../..');
        }

        $this->setupAutoloader();

        if ($configDir === null) {
            $configDir = getenv('ICINGAWEB_CONFIGDIR');
            if ($configDir === false) {
                $configDir = Platform::isWindows()
                    ? $baseDir . '/config'
                    : '/etc/icingaweb2';
            }
        }
        $canonical = realpath($configDir);
        $this->configDir = $canonical ? $canonical : $configDir;

        if ($storageDir === null) {
            $storageDir = getenv('ICINGAWEB_STORAGEDIR');
            if ($storageDir === false) {
                $storageDir = Platform::isWindows()
                    ? $baseDir . '/storage'
                    : '/var/lib/icingaweb2';
            }
        }
        $canonical = realpath($storageDir);
        $this->storageDir = $canonical ? $canonical : $storageDir;

        if ($this->libraryPaths === null) {
            $libraryPaths = getenv('ICINGAWEB_LIBDIR');
            if ($libraryPaths !== false) {
                $this->libraryPaths = array_filter(array_map(
                    'realpath',
                    explode(':', $libraryPaths)
                ), 'is_dir');
            } else {
                $this->libraryPaths = is_dir('/usr/share/icinga-php')
                    ? ['/usr/share/icinga-php']
                    : [];
            }
        }

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
     * Get loaded external libraries
     *
     * @return Libraries
     */
    public function getLibraries()
    {
        return $this->libraries;
    }

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
     * @return ClassLoader
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
     * Get the common storage directory
     *
     * @param   string $subDir Optional sub directory to get
     *
     * @return  string
     */
    public function getStorageDir($subDir = null)
    {
        return $this->getDirWithSubDir($this->storageDir, $subDir);
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
     * Setup Icinga class loader
     *
     * @return $this
     */
    public function setupAutoloader()
    {
        require_once $this->libDir . '/Icinga/Application/ClassLoader.php';

        $this->loader = new ClassLoader();
        $this->loader->registerNamespace('Icinga', $this->libDir . '/Icinga');
        $this->loader->registerNamespace('Icinga', $this->libDir . '/Icinga', $this->appDir);
        $this->loader->register();

        return $this;
    }

    /**
     * Setup module manager
     *
     * @return $this
     */
    protected function setupModuleManager()
    {
        $paths = $this->getAvailableModulePaths();
        $this->moduleManager = new ModuleManager(
            $this,
            $this->configDir . '/enabledModules',
            $paths
        );
        return $this;
    }

    protected function getAvailableModulePaths()
    {
        $paths = [];

        $configured = getenv('ICINGAWEB_MODULES_DIR');
        if (! $configured) {
            $configured = $this->config->get('global', 'module_path', $this->baseDir . '/modules');
        }

        $nextIsPhar = false;
        foreach (explode(PATH_SEPARATOR, $configured) as $path) {
            if ($path === 'phar') {
                $nextIsPhar = true;
                continue;
            }

            if ($nextIsPhar) {
                $nextIsPhar = false;
                $paths[] = 'phar:' . $path;
            } else {
                $paths[] = $path;
            }
        }

        return $paths;
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
            if ($this->moduleManager->hasInstalled('setup')) {
                $this->moduleManager->loadModule('setup');
            }
        } elseif ($this->setupTokenExists()) {
            // Load setup module but do not require setup
            if ($this->moduleManager->hasInstalled('setup')) {
                $this->moduleManager->loadModule('setup');
            }
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
     * Load external libraries
     *
     * @return $this
     */
    protected function loadLibraries()
    {
        $this->libraries = new Libraries();
        foreach ($this->libraryPaths as $libraryPath) {
            foreach (new DirectoryIterator($libraryPath) as $path) {
                if (! $path->isDot() && is_dir($path->getRealPath())) {
                    $this->libraries->registerPath($path->getPathname())
                        ->registerAutoloader();
                }
            }
        }

        return $this;
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
            if (! (error_reporting() & $errno)) {
                // Error was suppressed with the @-operator
                return false; // Continue with the normal error handler
            }
            switch ($errno) {
                case E_NOTICE:
                case E_WARNING:
                case E_STRICT:
                case E_RECOVERABLE_ERROR:
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
     * Set up the user backend factory
     *
     * @return  $this
     */
    protected function setupUserBackendFactory()
    {
        try {
            UserBackend::setConfig(Config::app('authentication'));
        } catch (NotReadableError $e) {
            Logger::error(
                new IcingaException('Cannot load user backend configuration. An exception was thrown:', $e)
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
    final protected function setupTimezone()
    {
        $timezone = $this->detectTimezone();
        if ($timezone === null || @date_default_timezone_set($timezone) === false) {
            date_default_timezone_set(@date_default_timezone_get());
        }
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
     * Prepare internationalization using gettext
     *
     * @return $this
     */
    protected function prepareInternationalization()
    {
        StaticTranslator::$instance = (new GettextTranslator())
            ->setDefaultDomain('icinga');

        return $this;
    }

    /**
     * Set up internationalization using gettext
     *
     * @return $this
     */
    final protected function setupInternationalization()
    {
        /** @var GettextTranslator $translator */
        $translator = StaticTranslator::$instance;

        if ($this->hasLocales()) {
            $translator->addTranslationDirectory($this->getLocaleDir(), 'icinga');
        }

        $locale = $this->detectLocale();
        if ($locale === null) {
            $locale = $translator->getDefaultLocale();
        }

        try {
            $translator->setLocale($locale);
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
        if ($this->localeDir === null) {
            $L10nLocales = getenv('ICINGAWEB_LOCALEDIR') ?: '/usr/share/icinga-L10n/locale';
            if (file_exists($L10nLocales) && is_dir($L10nLocales)) {
                $this->localeDir = $L10nLocales;
            } else {
                $this->localeDir = false;
            }
        }

        return $this->localeDir;
    }

    /**
     * return bool Whether Icinga Web has translations
     */
    public function hasLocales()
    {
        $localedir = $this->getLocaleDir();
        return $localedir !== false && file_exists($localedir) && is_dir($localedir);
    }
}
