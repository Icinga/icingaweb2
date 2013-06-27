<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Icinga\Application\Logger as Log;
use Icinga\Exception\ProgrammingError;

/**
 * Class Hook
 *
 * Register and use hook classes
 *
 * @package Icinga\Web
 */
class Hook
{
    /**
     * @var array
     */
    protected static $hooks = array();

    /**
     * @var array
     */
    protected static $instances = array();

    /**
     * @var string
     */
    public static $BASE_NS = 'Icinga\\Web\\Hook\\';

    /**
     *
     */
    public static function clean()
    {
        self::$hooks = array();
        self::$instances = array();
        self::$BASE_NS = 'Icinga\\Web\\Hook\\';
    }

    /**
     * Test hook names or keys
     * @param string $name
     * @param null $key
     * @return bool
     */
    public static function has($name, $key = null)
    {
        if ($key !== null) {
            return isset(self::$hooks[$name][$key]);
        } else {
            return isset(self::$hooks[$name]);
        }
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
     * Registers a class
     * @deprecated Too generic, throw away
     * @param string $name
     * @param string $key
     * @param string $class
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
