<?php

/**
 * Icinga Web Hook registry
 */
namespace Icinga\Web;

use Icinga\Application\Logger as Log;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\NotImplementedError;

/**
 * Icinga Web Hook registry
 *
 * Modules making use of predefined hooks have to use this registry
 *
 * Usage:
 * <code>
 * Hook::register('grapher', 'My\\Grapher\\Class');
 * </code>
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
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
     * @var string
     */
    public static $BASE_NS = 'Icinga\\Web\\Hook\\';

    public static function clean()
    {
        self::$hooks = array();
        self::$instances = array();
        self::$BASE_NS = 'Icinga\\Web\\Hook\\';
    }


    /**
     * Whether someone registered itself for the given hook name
     *
     * @param  string $name  One of the predefined hook names
     *
     * @return bool
     */
    public static function has($name)
    {
        return array_key_exists($name, self::$hooks);
    }

    /**
     * Get the first registered instance for the given hook name
     *
     * TODO: Multiple instances are not handled yet
     * TODO: Should return some kind of a hook interface
     *
     * @param  string $name  One of the predefined hook names
     *
     * @return mixed
     */
    public static function get($name)
    {
        if (! self::has($name)) {
            return null;
        }
        if (! array_key_exists($name, self::$instances)) {
            $class = self::$hooks[$name];
            try {
                $obj = new $class();
            } catch (\Exception $e) {
                // TODO: Persist unloading for "some time" or "current session"
                Log::debug(
                    'Hook "%s" (%s) failed, will be unloaded: %s',
                    $name,
                    $class,
                    $e->getMessage()
                );
                unset(self::$hooks[$name]);
                return null;
            }
            $base_class = 'Icinga\\Web\\Hook\\' . ucfirst($name);
            if (! $obj instanceof $base_class) {
                throw new ProgrammingError(
                    sprintf(
                        '%s is not an instance of %s',
                        get_class($obj),
                        $base_class
                    )
                );
            }
            self::$instances[$name] = $obj;

        }
        return self::$instances[$name];
    }

    /**
     * Create or return an instance of the hook
     * @param string $name
     * @param string $key
     * @return \stdClass
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
        } catch (\Exception $e) {
            Log::debug(
                'Hook "%s" (%s) (%s) failed, will be unloaded: %s',
                $name,
                $key,
                $class,
                $e->getMessage()
            );
            unset(self::$hooks[$name][$key]);
            return null;
        }
        self::assertValidHook($instance, $name);
        self::$instances[$name][$key] = $instance;
        return $instance;
    }

    /**
     * Test for a valid class name
     * @param \stdClass $instance
     * @param string $name
     * @throws \Icinga\Exception\ProgrammingError
     */
    private static function assertValidHook(&$instance, $name)
    {
        $base_class = self::$BASE_NS . ucfirst($name);
        if (!$instance instanceof $base_class) {
            throw new ProgrammingError(
                sprintf(
                    '%s is not an instance of %s',
                    get_class($instance),
                    $base_class
                )
            );
        }
    }

    /**
     * Return all instances of a specific name
     * @param string $name
     * @return array
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
     * @param string $name
     * @return \stdClass
     */
    public static function first($name)
    {
        return self::createInstance($name, key(self::$hooks[$name]));
    }

    /**
     * Register your hook
     *
     * @param  string $name  One of the predefined hook names
     * @param  string $class Your class name, must inherit one of the classes
     *                       in the Icinga/Web/Hook folder
     *
     * @throws NotImplementedError unless we support multiple instances
     *
     * @return void
     */
    public static function register($name, $key, $class)
    {
        self::registerClass($name, $key, $class);
    }

    /**
     * Register a class
     *
     * @param string $name
     * @param string $key
     * @param string $class
     */
    public static function registerClass($name, $key, $class)
    {
        if (!isset(self::$hooks[$name])) {
            self::$hooks[$name] = array();
        }

        self::$hooks[$name][$key] = $class;
    }

    /**
     * Register an object
     * @param string $name
     * @param string $key
     * @param object $object
     * @throws \Icinga\Exception\ProgrammingError
     */
    public static function registerObject($name, $key, $object)
    {
        if (!is_object($object)) {
            throw new ProgrammingError('object is not an instantiated class');
        }

        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = array();
        }

        self::$instances[$name][$key] =& $object;
        self::registerClass($name, $key, get_class($object));
    }

}
