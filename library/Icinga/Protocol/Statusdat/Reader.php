<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat;

use Icinga\Util\File;
use Icinga\Logger\Logger;
use Icinga\Data\Selectable;
use Icinga\Exception\ConfigurationError;

/**
 * Class Reader
 * @package Icinga\Protocol\Statusdat
 */
class Reader implements IReader, Selectable
{
    /**
     *  The default lifetime of the cache in milliseconds
     */
    const DEFAULT_CACHE_LIFETIME = 30;

    /**
     *  The folder for the statusdat cache
     */
    const STATUSDAT_DEFAULT_CACHE_PATH = '/tmp';

    /**
     * The last state from the cache
     *
     * @var array
     */
    private $lastState;

    /**
     * True when this reader has already acquired the current runtime state (i.e. Status.dat)
     *
     * @var bool
     */
    private $hasRuntimeState = false;

    /**
     * The representation of the object.cache file
     *
     * @var array
     */
    private $objectCache ;

    /**
     * The representation of the status.dat file
     * @var array
     */
    private $statusCache;

    /**
     * True when the icinga state differs from the cache
     *
     * @var bool
     */
    private $newState = false;

    /**
     * The Parser object to use for parsing
     *
     * @var Parser
     */
    private $parser;

    /**
     * Whether to disable the cache
     *
     * @var bool
     */
    private $noCache;

    /**
     * Create a new Reader from the given configuraion
     *
     * @param Zend_Config $config   The configuration to read the status.dat information from
     * @param Parser $parser        The parser to use (for testing)
     * @param bool $noCache         Whether to disable the cache
     */
    public function __construct($config = \Zend_Config, $parser = null, $noCache = false)
    {
        $this->noCache = $noCache;
        if (isset($config->no_cache)) {
            $this->noCache = $config->no_cache;
        }
        $this->config = $config;
        $this->parser = $parser;

        if (!$this->noCache) {
            $this->cache = $this->initializeCaches($config);
            if ($this->fromCache()) {
                $this->createHostServiceConnections();
                return;
            }
        }

        if (!$this->lastState) {
            $this->parseObjectsCacheFile();
        }
        if (!$this->hasRuntimeState) {

        }
        $this->parseStatusDatFile();
        if (!$noCache && $this->newState) {
            $this->statusCache->save($this->parser->getRuntimeState(), 'object' . md5($this->config->object_file));
        }
        $this->createHostServiceConnections();

    }

    /**
     * Initialize the internal caches if enabled
     *
     * @throws ConfigurationError
     */
    private function initializeCaches()
    {
        $defaultCachePath   = self::STATUSDAT_DEFAULT_CACHE_PATH;
        $cachePath          = $this->config->get('cache_path', $defaultCachePath);
        $maxCacheLifetime   = intval($this->config->get('cache_path', self::DEFAULT_CACHE_LIFETIME));
        $cachingEnabled     = true;
        if (!is_writeable($cachePath)) {
            Logger::warning(
                'Can\'t cache Status.dat backend; make sure cachepath %s is writable by the web user. '
                . 'Caching is now disabled',
                $cachePath
            );
            $cachePath = null;
        }
        $backendOptions = array(
            'cache_dir' => $cachePath
        );
        // the object cache might exist for months and is still valid
        $this->objectCache = $this->initCache($this->config->object_file, $backendOptions, null, $cachingEnabled);
        $this->statusCache = $this->initCache(
            $this->config->status_file,
            $backendOptions,
            $maxCacheLifetime,
            $cachingEnabled
        );
    }

    /**
     * Init the Cache backend in Zend
     *
     * @param String        $file       The file to use as the cache master file
     * @param Zend_Config   $backend    The backend configuration to use
     * @param integer       $lifetime  The lifetime of the cache
     *
     * @return \Zend_Cache_Core|\Zend_Cache_Frontend
     */
    private function initCache($file, $backendConfig, $lifetime)
    {
        $frontendOptions = array(
            'lifetime'                  => $lifetime,
            'automatic_serialization'   => true,
            'master_files'              => array($file)
        );
        return \Zend_Cache::factory('Core', 'File', $frontendOptions, $backendConfig);
    }

    /**
     * Read the current cache state
     *
     * @return bool     True if the state is the same as the icinga state
     */
    private function fromCache()
    {
        if (!$this->readObjectsCache()) {
            $this->newState = true;
            return false;
        }
        if (!$this->readStatusCache()) {
            $this->newState = true;
            return false;
        }
        return true;
    }

    /**
     * Read the object.cache file from the Zend_Cache backend
     *
     * @return bool     True if the file could be loaded from cache
     */
    private function readObjectsCache()
    {
        $this->lastState = $this->objectCache->load('object' . md5($this->config->object_file));
        if ($this->lastState == false) {
            return false;
        }

        return true;
    }

    /**
     * Read the status.dat file from the Zend_Cache backend
     *
     * @return bool     True if the file could be loaded from cache
     */
    private function readStatusCache()
    {
        if (!isset($this->stateCache)) {
            return true;
        }
        $statusInfo = $this->stateCache->load('state' . md5($this->config->status_file));
        if ($statusInfo == false) {
            return false;
        }

        $this->hasRuntimeState = true;
        return true;
    }

    /**
     * Take the status.dat and objects.cache and connect all services to hosts
     *
     */
    private function createHostServiceConnections()
    {
        if (!isset($this->lastState["service"])) {
            return;
        }
        foreach ($this->lastState["host"] as &$host) {
            $host->host = $host;
        }
        foreach ($this->lastState["service"] as &$service) {
            $service->service = &$service; // allow easier querying
            $host = &$this->lastState["host"][$service->host_name];
            if (!isset($host->services)) {
                $host->services = array();
            }
            $host->services[$service->service_description] = & $service;
            $service->host = & $host;
        }
    }

    /**
     * Parse the object.cache file and update the current state
     *
     * @throws ConfigurationError   If the object.cache couldn't be read
     */
    private function parseObjectsCacheFile()
    {
        if (!is_readable($this->config->object_file)) {
            throw new ConfigurationError(
                'Can\'t read object-file "' . $this->config->object_file . '", check your configuration'
            );
        }
        if (!$this->parser) {
            $this->parser = new Parser(new File($this->config->object_file, 'r'));
        }
        $this->parser->parseObjectsFile();
        $this->lastState = $this->parser->getRuntimeState();
    }

    /**
     * Parse the status.dat file and update the current state
     *
     * @throws ConfigurationError   If the status.dat couldn't be read
     */
    private function parseStatusDatFile()
    {
        if (!is_readable($this->config->status_file)) {
            throw new ConfigurationError(
                "Can't read status-file {$this->config->status_file}, check your configuration"
            );
        }
        if (!$this->parser) {
            $this->parser = new Parser(new File($this->config->status_file, 'r'), $this->lastState);
        }
        $this->parser->parseRuntimeState(new File($this->config->status_file, 'r'));
        $this->lastState = $this->parser->getRuntimeState();
        if (!$this->noCache) {
            $this->statusCache->save(array("true" => true), "state" . md5($this->config->object_file));
        }
    }

    /**
     * Create a new Query
     *
     * @return Query        The query to operate on
     */
    public function select()
    {
        return new Query($this);
    }

    /**
     * Return the internal state of the status.dat
     *
     * @return mixed        The internal status.dat representation
     */
    public function getState()
    {
        return $this->lastState;
    }


    /**
     * Return the object with the given name and type
     *
     * @param  String $type                 The type of the object to return (service, host, servicegroup...)
     * @param  String $name                 The name of the object
     *
     * @return ObjectContainer              An object container wrapping the result or null if the object doesn't exist
     */
    public function getObjectByName($type, $name)
    {
        if (isset($this->lastState[$type]) && isset($this->lastState[$type][$name])) {
            return new ObjectContainer($this->lastState[$type][$name], $this);
        }
        return null;
    }

    /**
     * Get an array containing all names of monitoring objects with the given type
     *
     * @param   String $type                The type of object to get the names for
     * @return  array                       An array of names or null if the type does not exist
     */
    public function getObjectNames($type)
    {
        return isset($this->lastState[$type]) ? array_keys($this->lastState[$type]) : null;
    }
}
