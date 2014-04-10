<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Application;

/**
 * Partially emulate the functionality of Zend_Db
 */
class ZendDbMock
{
    /**
     * The config that was used in the last call of the factory function
     *
     * @var mixed
     */
    private static $config;

    /**
     * Name of the adapter class that was used in the last call of the factory function
     *
     * @var mixed
     */
    private static $adapter;

    /**
     * Mock the factory-method of Zend_Db and save the given parameters
     *
     * @param   $adapter    String  name of base adapter class, or Zend_Config object
     * @param   $config     mixed   OPTIONAL; an array or Zend_Config object with adapter
     *                              parameters
     *
     * @return  stdClass    Empty object
     */
    public static function factory($adapter, $config)
    {
        self::$config = $config;
        self::$adapter = $adapter;
        return new \stdClass();
    }

    /**
     * Get the name of the adapter class that was used in the last call
     * of the factory function
     *
     * @return String
     */
    public static function getAdapter()
    {
        return self::$adapter;
    }

    /**
     * Get the config that was used in the last call of the factory function
     *
     * @return mixed
     */
    public static function getConfig()
    {
        return self::$config;
    }
}
