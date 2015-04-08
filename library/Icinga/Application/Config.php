<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

use Iterator;
use Countable;
use LogicException;
use UnexpectedValueException;
use Icinga\Util\File;
use Icinga\Data\ConfigObject;
use Icinga\File\Ini\IniWriter;
use Icinga\Exception\NotReadableError;

/**
 * Container for INI like configuration and global registry of application and module related configuration.
 */
class Config implements Countable, Iterator
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
     * The internal ConfigObject
     *
     * @var ConfigObject
     */
    protected $config;

    /**
     * The INI file this config has been loaded from or should be written to
     *
     * @var string
     */
    protected $configFile;

    /**
     * Create a new config
     *
     * @param   ConfigObject    $config     The config object to handle
     */
    public function __construct(ConfigObject $config = null)
    {
        $this->config = $config !== null ? $config : new ConfigObject();
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
     * @param   string      $filepath   The path to the ini file
     *
     * @return  $this
     */
    public function setConfigFile($filepath)
    {
        $this->configFile = $filepath;
        return $this;
    }

    /**
     * Return the count of available sections
     *
     * @return  int
     */
    public function count()
    {
        return $this->config->count();
    }

    /**
     * Reset the current position of the internal config object
     *
     * @return  ConfigObject
     */
    public function rewind()
    {
        return $this->config->rewind();
    }

    /**
     * Return the section of the current iteration
     *
     * @return  ConfigObject
     */
    public function current()
    {
        return $this->config->current();
    }

    /**
     * Return whether the position of the current iteration is valid
     *
     * @return  bool
     */
    public function valid()
    {
        return $this->config->valid();
    }

    /**
     * Return the section's name of the current iteration
     *
     * @return  string
     */
    public function key()
    {
        return $this->config->key();
    }

    /**
     * Advance the position of the current iteration and return the new section
     *
     * @return  ConfigObject
     */
    public function next()
    {
        return $this->config->next();
    }

    /**
     * Return whether this config has any sections
     *
     * @return  bool
     */
    public function isEmpty()
    {
        return $this->config->isEmpty();
    }

    /**
     * Return this config's section names
     *
     * @return  array
     */
    public function keys()
    {
        return $this->config->keys();
    }

    /**
     * Return this config's data as associative array
     *
     * @return  array
     */
    public function toArray()
    {
        return $this->config->toArray();
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
    public function get($section, $key, $default = null)
    {
        $value = $this->config->$section;
        if ($value instanceof ConfigObject) {
            $value = $value->$key;
        } elseif ($value !== null) {
            throw new UnexpectedValueException(
                sprintf('Value "%s" is not of type "%s" or a sub-type of it', $value, get_class($this->config))
            );
        }

        if ($value === null && $default !== null) {
            $value = $default;
        }

        return $value;
    }

    /**
     * Return the given section
     *
     * @param   string  $name   The section's name
     *
     * @return  ConfigObject
     */
    public function getSection($name)
    {
        $section = $this->config->get($name);
        return $section !== null ? $section : new ConfigObject();
    }

    /**
     * Set or replace a section
     *
     * @param   string              $name
     * @param   array|ConfigObject  $config
     *
     * @return  $this
     */
    public function setSection($name, $config = null)
    {
        if ($config === null) {
            $config = new ConfigObject();
        } elseif (! $config instanceof ConfigObject) {
            $config = new ConfigObject($config);
        }

        $this->config->$name = $config;
        return $this;
    }

    /**
     * Remove a section
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function removeSection($name)
    {
        unset($this->config->$name);
        return $this;
    }

    /**
     * Return whether the given section exists
     *
     * @param   string  $name
     *
     * @return  bool
     */
    public function hasSection($name)
    {
        return isset($this->config->$name);
    }

    /**
     * Initialize a new config using the given array
     *
     * The returned config has no file associated to it.
     *
     * @param   array   $array      The array to initialize the config with
     *
     * @return  Config
     */
    public static function fromArray(array $array)
    {
        return new static(new ConfigObject($array));
    }

    /**
     * Load configuration from the given INI file
     *
     * @param   string      $file   The file to parse
     *
     * @throws  NotReadableError    When the file cannot be read
     */
    public static function fromIni($file)
    {
        $emptyConfig = new static();

        $filepath = realpath($file);
        if ($filepath === false) {
            $emptyConfig->setConfigFile($file);
        } elseif (is_readable($filepath)) {
            $config = new static(new ConfigObject(parse_ini_file($filepath, true)));
            $config->setConfigFile($filepath);
            return $config;
        } elseif (@file_exists($filepath)) {
            throw new NotReadableError(t('Cannot read config file "%s". Permission denied'), $filepath);
        }

        return $emptyConfig;
    }

    /**
     * Save configuration to the given INI file
     *
     * @param   string|null     $filePath   The path to the INI file or null in case this config's path should be used
     * @param   int             $fileMode   The file mode to store the file with
     *
     * @throws  LogicException              In case this config has no path and none is passed in either
     * @throws  NotWritableError            In case the INI file cannot be written
     *
     * @todo    create basepath and throw NotWritableError in case its not possible
     */
    public function saveIni($filePath = null, $fileMode = 0660)
    {
        if ($filePath === null && $this->configFile) {
            $filePath = $this->configFile;
        } elseif ($filePath === null) {
            throw new LogicException('You need to pass $filePath or set a path using Config::setConfigFile()');
        }

        if (! file_exists($filePath)) {
            File::create($filePath, $fileMode);
        }

        $this->getIniWriter($filePath, $fileMode)->write();
    }

    /**
     * Return a IniWriter for this config
     *
     * @param   string|null     $filePath
     * @param   int             $fileMode
     *
     * @return  IniWriter
     */
    protected function getIniWriter($filePath = null, $fileMode = null)
    {
        return new IniWriter(array(
            'config' => $this,
            'filename' => $filePath,
            'filemode' => $fileMode
        ));
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
        if (! isset(self::$app[$configname]) || $fromDisk) {
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
        if (! isset(self::$modules[$modulename])) {
            self::$modules[$modulename] = array();
        }

        $moduleConfigs = self::$modules[$modulename];
        if (! isset($moduleConfigs[$configname]) || $fromDisk) {
            $moduleConfigs[$configname] = static::fromIni(
                static::resolvePath('modules/' . $modulename . '/' . $configname . '.ini')
            );
        }

        return $moduleConfigs[$configname];
    }

    /**
     * Return this config rendered as a INI structured string
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->getIniWriter()->render();
    }
}
