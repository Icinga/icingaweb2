<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Iterator;
use Countable;
use ArrayAccess;
use LogicException;
use UnexpectedValueException;
use Icinga\Exception\NotReadableError;

/**
 * Container for configuration values and global registry of application and module related configuration.
 */
class Config implements Countable, Iterator, ArrayAccess
{
    /**
     * Configuration directory where ALL (application and module) configuration is located
     *
     * @var string
     */
    public static $configDir;

    /**
     * Application config instances per file
     *
     * @var array
     */
    protected static $app = array();

    /**
     * Module config instances per file
     *
     * @var array
     */
    protected static $modules = array();

    /**
     * This config's data
     *
     * @var array
     */
    protected $data;

    /**
     * The INI file this configuration has been loaded from or should be written to
     *
     * @var string
     */
    protected $configFile;

    /**
     * Create a new config
     *
     * @param   array   $data   The data to initialize the new config with
     */
    public function __construct(array $data = array())
    {
        $this->data = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->data[$key] = new static($value);
            } else {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * Return this config's file path
     *
     * @return  string
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Set this config's file path
     *
     * @param   string      $filepath   The path to the config file
     *
     * @return  self
     */
    public function setConfigFile($filepath)
    {
        $this->configFile = $filepath;
        return $this;
    }

    /**
     * Deep clone this config
     */
    public function __clone()
    {
        $array = array();
        foreach ($this->data as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = clone $value;
            } else {
                $array[$key] = $value;
            }
        }

        $this->data = $array;
    }

    /**
     * Return the count of available sections and properties
     *
     * @return  int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Reset the current position of $this->data
     *
     * @return  mixed
     */
    public function rewind()
    {
        return reset($this->data);
    }

    /**
     * Return the section's or property's value of the current iteration
     *
     * @return  mixed
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * Return whether the position of the current iteration is valid
     *
     * @return  bool
     */
    public function valid()
    {
        return key($this->data) !== null;
    }

    /**
     * Return the section's or property's name of the current iteration
     *
     * @return  mixed
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * Advance the position of the current iteration and return the new section's or property's value
     *
     * @return  mixed
     */
    public function next()
    {
        return next($this->data);
    }

    /**
     * Return whether the given section or property is set
     *
     * @param   string  $key    The name of the section or property
     *
     * @return  bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Return the value for the given property or the config for the given section
     *
     * @param   string  $key    The name of the property or section
     *
     * @return  mixed|NULL      The value or NULL in case $key does not exist
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
    }

    /**
     * Add a new property or section
     *
     * @param   string  $key    The name of the new property or section
     * @param   mixed   $value  The value to set for the new property or section
     */
    public function __set($key, $value)
    {
        if (is_array($value)) {
            $this->data[$key] = new static($value);
        } else {
            $this->data[$key] = $value;
        }
    }

    /**
     * Remove the given property or section
     *
     * @param   string  $key    The property or section to remove
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Return whether the given section or property is set
     *
     * @param   string  $key    The name of the section or property
     *
     * @return  bool
     */
    public function offsetExists($key)
    {
        return isset($this->$key);
    }

    /**
     * Return the value for the given property or the config for the given section
     *
     * @param   string  $key    The name of the property or section
     *
     * @return  mixed|NULL      The value or NULL in case $key does not exist
     */
    public function offsetGet($key)
    {
        return $this->$key;
    }

    /**
     * Add a new property or section
     *
     * @param   string  $key    The name of the new property or section
     * @param   mixed   $value  The value to set for the new property or section
     */
    public function offsetSet($key, $value)
    {
        if ($key === null) {
            throw new LogicException('Appending values without an explicit key is not supported');
        }

        $this->$key = $value;
    }

    /**
     * Remove the given property or section
     *
     * @param   string  $key    The property or section to remove
     */
    public function offsetUnset($key)
    {
        unset($this->$key);
    }

    /**
     * Return whether this config has any data
     *
     * @return  bool
     */
    public function isEmpty()
    {
        return $this->count() === 0;
    }

    /**
     * Return the value for the given property or the config for the given section
     *
     * @param   string  $key        The name of the property or section
     * @param   mixed   $default    The value to return in case the property or section is missing
     *
     * @return  mixed
     */
    public function get($key, $default = null)
    {
        $value = $this->$key;
        if ($default !== null && $value === null) {
            $value = $default;
        }

        return $value;
    }

    /**
     * Return all section and property names
     *
     * @return  array
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Return this config's data as associative array
     *
     * @return  array
     */
    public function toArray()
    {
        $array = array();
        foreach ($this->data as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * Merge the given data with this config
     *
     * @param   array|Config    $data   An array or a config
     *
     * @return  self
     */
    public function merge($data)
    {
        if ($data instanceof self) {
            $data = $data->toArray();
        }

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                if (is_array($value)) {
                    if ($this->data[$key] instanceof self) {
                        $this->data[$key]->merge($value);
                    } else {
                        $this->data[$key] = new static($value);
                    }
                } else {
                    $this->data[$key] = $value;
                }
            } else {
                $this->data[$key] = is_array($value) ? new static($value) : $value;
            }
        }
    }

    /**
     * Return the value from a section's property
     *
     * @param   string  $section    The section where the given property can be found
     * @param   string  $key        The section's property to fetch the value from
     * @param   mixed   $default    The value to return in case the section or the property is missing
     *
     * @return  mixed
     *
     * @throws  UnexpectedValueException    In case the given section does not hold any configuration
     */
    public function fromSection($section, $key, $default = null)
    {
        $value = $this->$section;
        if ($value instanceof self) {
            $value = $value->$key;
        } elseif ($value !== null) {
            throw new UnexpectedValueException(
                sprintf('Value "%s" is not of type "Config" or a sub-type of it', $value)
            );
        }

        if ($default !== null) {
            $value = $default;
        }

        return $value;
    }

    /**
     * Load configuration from the given INI file
     *
     * @param   string      $file   The file to parse
     *
     * @throws  NotReadableError    When the file does not exist or cannot be read
     */
    public static function fromIni($file)
    {
        $config = new static();

        $filepath = realpath($file);
        if ($filepath === false) {
            $config->setConfigFile($file);
        } elseif (is_readable($filepath)) {
            $config->setConfigFile($filepath);
            $config->merge(parse_ini_file($filepath, true, INI_SCANNER_RAW));
        } else {
            throw new NotReadableError(t('Cannot read config file "%s". Permission denied'), $filepath);
        }

        return $config;
    }

    /**
     * Prepend configuration base dir to the given relative path
     *
     * @param   string  $path   A relative path
     *
     * @return  string
     */
    public static function resolvePath($path)
    {
        return self::$configDir . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Retrieve a application config
     *
     * @param   string  $configname     The configuration name (without ini suffix) to read and return
     * @param   bool    $fromDisk       When set true, the configuration will be read from disk, even
     *                                  if it already has been read
     *
     * @return  Config                  The requested configuration
     */
    public static function app($configname = 'config', $fromDisk = false)
    {
        if (!isset(self::$app[$configname]) || $fromDisk) {
            self::$app[$configname] = static::fromIni(static::resolvePath($configname . '.ini'));
        }

        return self::$app[$configname];
    }

    /**
     * Retrieve a module config
     *
     * @param   string  $modulename     The name of the module where to look for the requested configuration
     * @param   string  $configname     The configuration name (without ini suffix) to read and return
     * @param   string  $fromDisk       When set true, the configuration will be read from disk, even
     *                                  if it already has been read
     *
     * @return  Config                  The requested configuration
     */
    public static function module($modulename, $configname = 'config', $fromDisk = false)
    {
        if (!isset(self::$modules[$modulename])) {
            self::$modules[$modulename] = array();
        }

        $moduleConfigs = self::$modules[$modulename];
        if (!isset($moduleConfigs[$configname]) || $fromDisk) {
            $moduleConfigs[$configname] = static::fromIni(
                static::resolvePath('modules/' . $modulename . '/' . $configname . '.ini')
            );
        }

        return $moduleConfigs[$configname];
    }
}
