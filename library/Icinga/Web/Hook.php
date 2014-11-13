<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Exception;
use Icinga\Application\Logger;
use Icinga\Exception\ProgrammingError;

/**
 * Icinga Web Hook registry
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
    public static $BASE_NS = 'Icinga\\Web\\Hook\\';

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
        self::$BASE_NS = 'Icinga\\Web\\Hook\\';
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
        return array_key_exists($name, self::$hooks);
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
        if (!self::has($name, $key)) {
            return null;
        }

        if (isset(self::$instances[$name][$key])) {
            return self::$instances[$name][$key];
        }

        $class = self::$hooks[$name][$key];
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
        $base_class = self::$BASE_NS . ucfirst($name) . 'Hook';

        if (strpos($base_class, self::$classSuffix) === false) {
            $base_class .= self::$classSuffix;
        }

        if (!$instance instanceof $base_class) {
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
        if (!self::has($name)) {
            return array();
        }

        foreach (self::$hooks[$name] as $key => $hook) {
            if (self::createInstance($name, $key) === null) {
                return array();
            }
        }

        return self::$instances[$name];
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
        if (self::has($name)) {
            return self::createInstance($name, key(self::$hooks[$name]));
        }
    }

    /**
     * Register a class
     *
     * @param   string      $name   One of the predefined hook names
     * @param   string      $key    The identifier of a specific subtype
     * @param   string      $class  Your class name, must inherit one of the
     *                              classes in the Icinga/Web/Hook folder
     */
    public static function register($name, $key, $class)
    {
        if (!isset(self::$hooks[$name])) {
            self::$hooks[$name] = array();
        }

        self::$hooks[$name][$key] = $class;
    }
}
