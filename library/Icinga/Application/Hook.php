<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

use Exception;
use Icinga\Authentication\Auth;
use Icinga\Application\Modules\Manager;
use Icinga\Exception\ProgrammingError;

/**
 * Icinga Hook registry
 *
 * Modules making use of predefined hooks have to use this registry
 *
 * Usage:
 * <code>
 * Hook::register('grapher', 'My\\Grapher\\Class');
 * </code>
 */
class Hook
{
    /**
     * Our hook name registry
     *
     * @var array
     */
    protected static $hooks = array();

    /**
     * Hooks that have already been instantiated
     *
     * @var array
     */
    protected static $instances = array();

    /**
     * Namespace prefix
     *
     * @var string
     */
    public static $BASE_NS = 'Icinga\\Application\\Hook\\';

    /**
     * Append this string to base class
     *
     * All base classes renamed to *Hook
     *
     * @var string
     */
    public static $classSuffix = 'Hook';

    /**
     * Reset object state
     */
    public static function clean()
    {
        self::$hooks = array();
        self::$instances = array();
        self::$BASE_NS = 'Icinga\\Application\\Hook\\';
    }

    /**
     * Whether someone registered itself for the given hook name
     *
     * @param   string  $name   One of the predefined hook names
     *
     * @return  bool
     */
    public static function has($name)
    {
        $name = self::normalizeHookName($name);
        return array_key_exists($name, self::$hooks);
    }

    protected static function normalizeHookName($name)
    {
        if (strpos($name, '\\') === false) {
            $parts = explode('/', $name);
            foreach ($parts as & $part) {
                $part = ucfirst($part);
            }

            return implode('\\', $parts);
        }

        return $name;
    }

    /**
     * Create or return an instance of a given hook
     *
     * TODO: Should return some kind of a hook interface
     *
     * @param   string  $name   One of the predefined hook names
     * @param   string  $key    The identifier of a specific subtype
     *
     * @return  mixed
     */
    public static function createInstance($name, $key)
    {
        $name = self::normalizeHookName($name);

        if (!self::has($name, $key)) {
            return null;
        }

        if (isset(self::$instances[$name][$key])) {
            return self::$instances[$name][$key];
        }

        $class = self::$hooks[$name][$key];

        if (! class_exists($class)) {
            throw new ProgrammingError(
                'Erraneous hook implementation, class "%s" does not exist',
                $class
            );
        }
        try {
            $instance = new $class();
        } catch (Exception $e) {
            Logger::debug(
                'Hook "%s" (%s) (%s) failed, will be unloaded: %s',
                $name,
                $key,
                $class,
                $e->getMessage()
            );
            // TODO: Persist unloading for "some time" or "current session"
            unset(self::$hooks[$name][$key]);
            return null;
        }

        self::assertValidHook($instance, $name);
        self::$instances[$name][$key] = $instance;
        return $instance;
    }

    protected static function splitHookName($name)
    {
        $sep = '\\';
        if (false === $module = strpos($name, $sep)) {
            return array(null, $name);
        }
        return array(
            substr($name, 0, $module),
            substr($name, $module + 1)
        );
    }

    /**
     * Extract the Icinga module name from a given namespaced class name
     *
     * Does no validation, prefix must have been checked before
     *
     * Shameless copy of ClassLoader::extractModuleName()
     *
     * @param   string  $class  The hook's class path
     *
     * @return string
     */
    protected static function extractModuleName($class)
    {
        return lcfirst(
            substr(
                $class,
                ClassLoader::MODULE_PREFIX_LENGTH,
                strpos(
                    $class,
                    ClassLoader::NAMESPACE_SEPARATOR,
                    ClassLoader::MODULE_PREFIX_LENGTH + 1
                ) - ClassLoader::MODULE_PREFIX_LENGTH
            )
        );
    }

    /**
     * Return whether the user has the permission to access the module which provides the given hook
     *
     * @param   string  $class  The hook's class path
     *
     * @return  bool
     */
    protected static function hasPermission($class)
    {
        if (Icinga::app()->isCli()) {
            return true;
        }

        return Auth::getInstance()->hasPermission(
            Manager::MODULE_PERMISSION_NS . self::extractModuleName($class)
        );
    }

    /**
     * Test for a valid class name
     *
     * @param   mixed   $instance
     * @param   string  $name
     *
     * @throws  ProgrammingError
     */
    private static function assertValidHook($instance, $name)
    {
        $name = self::normalizeHookName($name);

        $suffix = self::$classSuffix; // 'Hook'
        $base = self::$BASE_NS;       // 'Icinga\\Web\\Hook\\'

        list($module, $name) = self::splitHookName($name);

        if ($module === null) {
            $base_class = $base . ucfirst($name) . 'Hook';

            // I'm unsure whether this makes sense. Unused and Wrong.
            if (strpos($base_class, $suffix) === false) {
                $base_class .= $suffix;
            }
        } else {
            $base_class = 'Icinga\\Module\\'
                        . ucfirst($module)
                        . '\\Hook\\'
                        . ucfirst($name)
                        . $suffix;
        }

        if (!$instance instanceof $base_class) {
            // This is a compatibility check. Should be removed one far day:
            if ($module !== null) {
                $compat_class = 'Icinga\\Module\\'
                              . ucfirst($module)
                              . '\\Web\\Hook\\'
                              . ucfirst($name)
                              . $suffix;

                if ($instance instanceof $compat_class) {
                    return;
                }
            }

            throw new ProgrammingError(
                '%s is not an instance of %s',
                get_class($instance),
                $base_class
            );
        }
    }

    /**
     * Return all instances of a specific name
     *
     * @param   string  $name   One of the predefined hook names
     *
     * @return  array
     */
    public static function all($name)
    {
        $name = self::normalizeHookName($name);
        if (! self::has($name)) {
            return array();
        }

        foreach (self::$hooks[$name] as $key => $hook) {
            if (self::hasPermission($hook)) {
                if (self::createInstance($name, $key) === null) {
                    return array();
                }
            }
        }

        return isset(self::$instances[$name]) ? self::$instances[$name] : array();
    }

    /**
     * Get the first hook
     *
     * @param   string  $name   One of the predefined hook names
     *
     * @return  null|mixed
     */
    public static function first($name)
    {
        $name = self::normalizeHookName($name);

        if (self::has($name)) {
            foreach (self::$hooks[$name] as $key => $hook) {
                if (self::hasPermission($hook)) {
                    return self::createInstance($name, $key);
                }
            }
        }
    }

    /**
     * Register a class
     *
     * @param   string      $name   One of the predefined hook names
     * @param   string      $key    The identifier of a specific subtype
     * @param   string      $class  Your class name, must inherit one of the
     *                              classes in the Icinga/Application/Hook folder
     */
    public static function register($name, $key, $class)
    {
        $name = self::normalizeHookName($name);

        if (!isset(self::$hooks[$name])) {
            self::$hooks[$name] = array();
        }

        $class = ltrim($class, ClassLoader::NAMESPACE_SEPARATOR);

        self::$hooks[$name][$key] = $class;
    }
}
