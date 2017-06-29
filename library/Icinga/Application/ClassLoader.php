<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

use Zend_Loader_Autoloader;

/**
 * PSR-4 class loader
 */
class ClassLoader
{
    /**
     * Namespace separator
     */
    const NAMESPACE_SEPARATOR = '\\';

    /**
     * Icinga Web 2 module namespace prefix
     */
    const MODULE_PREFIX = 'Icinga\\Module\\';

    /**
     * Icinga Web 2 module namespace prefix length
     *
     * Helps to make substr/strpos operations even faster
     */
    const MODULE_PREFIX_LENGTH = 14;

    /**
     * A hardcoded class/subdir map for application ns prefixes
     *
     * When a module registers with an application directory, those
     * namespace prefixes (after the module prefix) will be looked up
     * in the corresponding application subdirectories
     *
     * @var array
     */
    protected $applicationPrefixes = array(
        'Clicommands' => 'clicommands',
        'Controllers' => 'controllers',
        'Forms'       => 'forms'
    );

    /**
     * Whether we already instantiated the ZF autoloader
     *
     * @var boolean
     */
    protected $gotZend = false;

    /**
     * Namespaces
     *
     * @var array
     */
    private $namespaces = array();

    /**
     * Application directories
     *
     * @var array
     */
    private $applicationDirectories = array();

    /**
     * Register a base directory for a namespace prefix
     *
     * Application directory is optional and provides additional lookup
     * logic for hardcoded namespaces like "Forms"
     *
     * @param   string  $namespace
     * @param   string  $directory
     * @param   string  $appDirectory
     *
     * @return  $this
     */
    public function registerNamespace($namespace, $directory, $appDirectory = null)
    {
        $this->namespaces[$namespace] = $directory;

        if ($appDirectory !== null) {
            $this->applicationDirectories[$namespace] = $appDirectory;
        }

        return $this;
    }

    /**
     * Test whether a namespace exists
     *
     * @param   string $namespace
     *
     * @return  bool
     */
    public function hasNamespace($namespace)
    {
        return array_key_exists($namespace, $this->namespaces);
    }

    /**
     * Get the source file of the given class or interface
     *
     * @param   string      $class Name of the class or interface
     *
     * @return  string|null
     */
    public function getSourceFile($class)
    {
        if ($file = $this->getModuleSourceFile($class)) {
            return $file;
        }

        foreach ($this->namespaces as $namespace => $dir) {
            if ($class === strstr($class, $namespace)) {
                return $this->buildClassFilename($class, $namespace);
            }
        }

        return null;
    }

    /**
     * Get the source file of the given module class or interface
     *
     * @param   string      $class Module class or interface name
     *
     * @return  string|null
     */
    protected function getModuleSourceFile($class)
    {
        if (! $this->classBelongsToModule($class)) {
            return null;
        }

        $modules = Icinga::app()->getModuleManager();
        $namespace = $this->extractModuleNamespace($class);

        if ($this->hasNamespace($namespace)) {
            return $this->buildClassFilename($class, $namespace);
        } elseif (! $modules->loadedAllEnabledModules()) {
            $moduleName = $this->extractModuleName($class);

            if ($modules->hasEnabled($moduleName)) {
                $modules->loadModule($moduleName);

                return $this->buildClassFilename($class, $namespace);
            }
        }

        return null;
    }

    /**
     * Extract the Icinga module namespace from a given namespaced class name
     *
     * Does no validation, prefix must have been checked before
     *
     * @return string
     */
    protected function extractModuleNamespace($class)
    {
        return substr(
            $class,
            0,
            strpos($class, self::NAMESPACE_SEPARATOR, self::MODULE_PREFIX_LENGTH + 1)
        );
    }

    /**
     * Extract the Icinga module name from a given namespaced class name
     *
     * Does no validation, prefix must have been checked before
     *
     * @return string
     */
    protected function extractModuleName($class)
    {
        return lcfirst(
            substr(
                $class,
                self::MODULE_PREFIX_LENGTH,
                strpos(
                    $class,
                    self::NAMESPACE_SEPARATOR,
                    self::MODULE_PREFIX_LENGTH + 1
                ) - self::MODULE_PREFIX_LENGTH
            )
        );
    }

    /**
     * Whether the given class name belongs to a module namespace
     *
     * @return boolean
     */
    protected function classBelongsToModule($class)
    {
        return substr($class, 0, self::MODULE_PREFIX_LENGTH) === self::MODULE_PREFIX;
    }

    /**
     * Prepare a filename string for the given class
     *
     * Expects the given namespace to be registered with a path name
     *
     * @return string
     */
    protected function buildClassFilename($class, $namespace)
    {
        $relNs = substr($class, strlen($namespace) + 1);

        if ($this->namespaceHasApplictionDirectory($namespace)) {
            $prefixSeparator = strpos($relNs, self::NAMESPACE_SEPARATOR);
            $prefix = substr($relNs, 0, $prefixSeparator);

            if ($this->isApplicationPrefix($prefix)) {
                return $this->applicationDirectories[$namespace]
                    . DIRECTORY_SEPARATOR
                    . $this->applicationPrefixes[$prefix]
                    . $this->classToRelativePhpFilename(substr($relNs, $prefixSeparator));
            }
        }

        return $this->namespaces[$namespace] . DIRECTORY_SEPARATOR . $this->classToRelativePhpFilename($relNs);
    }

    /**
     * Return the relative file name for the given (namespaces) class
     *
     * @param  string $class
     *
     * @return string
     */
    protected function classToRelativePhpFilename($class)
    {
        return str_replace(
            self::NAMESPACE_SEPARATOR,
            DIRECTORY_SEPARATOR,
            $class
        ) . '.php';
    }

    /**
     * Whether given prefix (Forms, Controllers...) makes part of "application"
     *
     * @param  string $prefix
     *
     * @return boolean
     */
    protected function isApplicationPrefix($prefix)
    {
        return array_key_exists($prefix, $this->applicationPrefixes);
    }

    /**
     * Whether the given namespace registered an application directory
     *
     * @return boolean
     */
    protected function namespaceHasApplictionDirectory($namespace)
    {
        return array_key_exists($namespace, $this->applicationDirectories);
    }

    /**
     * Require ZF autoloader
     *
     * @return Zend_Loader_Autoloader
     */
    protected function requireZendAutoloader()
    {
        require_once 'Zend/Loader/Autoloader.php';
        $this->gotZend = true;
        return Zend_Loader_Autoloader::getInstance();
    }

    /**
     * Load the given class or interface
     *
     * @param   string  $class  Name of the class or interface
     *
     * @return  bool            Whether the class or interface has been loaded
     */
    public function loadClass($class)
    {
        // We are aware of the Zend_ prefix and lazyload it's autoloader.
        // Return as fast as possible if we already did so.
        if (substr($class, 0, 5) === 'Zend_') {
            if (! $this->gotZend) {
                $zendLoader = $this->requireZendAutoloader();
                if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
                    // PHP7 seems to remember the autoload function stack before auto-loading. Thus
                    // autoload functions registered during autoload never get called
                    return $zendLoader::autoload($class);
                }
            }
            return false;
        }

        if ($file = $this->getSourceFile($class)) {
            if (file_exists($file)) {
                require $file;
                return true;
            }
        }

        return false;
    }

    /**
     * Register {@link loadClass()} as an autoloader
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Unregister {@link loadClass()} as an autoloader
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Unregister this as an autoloader
     */
    public function __destruct()
    {
        $this->unregister();
    }
}
