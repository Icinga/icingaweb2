<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

use Icinga\Exception\ProgrammingError;

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
     * Load the given class or interface
     *
     * @param   string  $class  Name of the class or interface
     *
     * @return  bool            Whether the class or interface has been loaded
     */
    public function loadClass($class)
    {
        $namespace = $this->getNamespaceForClass($class);

        if ($namespace) {
            $file = $this->namespaces[$namespace] . preg_replace('/^' . preg_quote($namespace) . '/', '', $class);
            $file = str_replace(self::NAMESPACE_SEPARATOR, '/', $file) . '.php';

            if (@file_exists($file)) {
                require_once $file;
                return true;
            }
        }

        return false;
    }

    /**
     * Get the namespace for the given class
     *
     * Return is the longest match in the array found
     *
     * @param   string  $className
     *
     * @return  bool|string
     */
    private function getNamespaceForClass($className)
    {
        $testNamespace = '';
        $testLength = 0;

        foreach (array_keys($this->namespaces) as $namespace) {
            $stub = preg_replace(
                '/^' . preg_quote($namespace) . '(' . preg_quote(self::NAMESPACE_SEPARATOR) . '|$)/', '', $className
            );
            $length = strlen($className) - strlen($stub);
            if ($length > $testLength) {
                $testLength = $length;
                $testNamespace = $namespace;
            }
        }

        if ($testLength > 0) {
            return $testNamespace;
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
     * Unregister this as an autloader
     */
    public function __destruct()
    {
        $this->unregister();
    }
}
