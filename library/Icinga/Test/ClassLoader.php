<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Test;

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
     * Namespaces
     *
     * @var array
     */
    private $namespaces = array();

    /**
     * Register a base directory for a namespace prefix
     *
     * @param   string  $namespace
     * @param   string  $directory
     *
     * @return  $this
     */
    public function registerNamespace($namespace, $directory)
    {
        $this->namespaces[$namespace] = $directory;

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
        foreach ($this->namespaces as $namespace => $dir) {
            if ($class === strstr($class, $namespace)) {
                $classPath = str_replace(
                    self::NAMESPACE_SEPARATOR,
                    DIRECTORY_SEPARATOR,
                    substr($class, strlen($namespace))
                ) . '.php';
                if (file_exists($file = $dir . $classPath)) {
                    return $file;
                }
            }
        }
        return null;
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
        if ($file = $this->getSourceFile($class)) {
            require $file;
            return true;
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
