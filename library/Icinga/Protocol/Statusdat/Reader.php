<?php
namespace Icinga\Protocol\Statusdat;

use Icinga\Exception as Exception;
use Icinga\Benchmark as Benchmark;

class ObjectContainer extends \stdClass {
    public $ref;
    public $reader;

    public function __construct(\stdClass &$obj,IReader &$reader) {
        $this->ref = &$obj;
        $this->reader = &$reader;

    }
    public function __get($attribute) {
        $exploded = explode(".",$attribute);
        $result = $this->ref;
        foreach($exploded as $elem) {

            $result = $result->$elem;
        }
        return $result;
    }
}

class Reader implements IReader
{
    const DEFAULT_CACHE_LIFETIME = 300;
    const STATUSDAT_DEFAULT_CACHE_PATH = "/cache";


    private $lastState = null;
    private $hasRuntimeState = false;
    private $objectCache = null;
    private $statusCache = null;
    private $newState = false;
    private $parser = null;
    private $noCache = false;
    public function __construct($config = \Zend_Config, $parser = null, $noCache = false)
    {
        $this->noCache = $noCache;
        $this->config = $config;
        $this->parser = $parser;
        if(!$noCache) {
            $this->cache = $this->initializeCaches($config);
            if($this->fromCache()) {
                $this->createHostServiceConnections();
                return;
            }
        }
        if(!$this->lastState)
            $this->parseObjectsCacheFile();
        if(!$this->hasRuntimeState);
            $this->parseStatusDatFile();
        if(!$noCache && $this->newState)
            $this->statusCache->save($this->parser->getRuntimeState(),'objects'.md5($this->config->objects_file));
        $this->createHostServiceConnections();

    }

    private function createHostServiceConnections()
    {
        if (!isset($this->lastState["service"])) {
            return;
        }

        foreach ($this->lastState["service"] as &$service) {
            $host = &$this->lastState["host"][$service->host_name];
            if(!isset($host->services))
                $host->services = array();
            $host->services[$service->service_description] = &$service;
            $service->host = &$host;
        }
    }

    public function select()
    {
        return new Query($this);
    }

    public function fetchAll(Query $query)
    {
        return new \Icinga\Backend\MonitoringObjectList(
            $query->getResult(),
            $query->getView()
        );
    }

    public function getState()
    {
        return $this->lastState;
    }

    public function getObjects()
    {
        return $this->lastState;
    }


    public function getObjectByName($type, $name)
    {
        if (isset($this->lastState[$type]) && isset($this->lastState[$type][$name]))
            return new ObjectContainer($this->lastState[$type][$name],$this);
        return null;
    }

    public function getObjectNames($type) {
        return isset($this->lastState[$type]) ? array_keys($this->lastState[$type]) : null;
    }

    private function fromCache()
    {
        if(!$this->readObjectsCache()) {
            $this->newState = true;
            return false;
        }
        if(!$this->readStatusCache()){
            $this->newState = true;
            return false;
        }


        return true;
    }

    private function readObjectsCache()
    {
        $this->lastState = $this->objectCache->load('objects'.md5($this->config->objects_file));
        if($this->lastState == false)
            return false;
    }

    private function readStatusCache()
    {
        $statusInfo = $this->stateCache->load('state'.md5($this->config->status_file));
        if($statusInfo == false)
            return false;
        $this->hasRuntimeState = true;
    }

    private function initializeCaches()
    {
        $defaultCachePath = "/tmp/".self::STATUSDAT_DEFAULT_CACHE_PATH;

        $cachePath = $this->config->get('cache_path',$defaultCachePath);
        $maxCacheLifetime = intval($this->config->get('cache_path',self::DEFAULT_CACHE_LIFETIME));
        if(!is_writeable($cachePath))
            throw new \Icinga\Exception\ConfigurationError("Cache path $cachePath is not writable, check your configuration");


        $backendOptions = array(
            'cache_dir' => $cachePath
        );
        // the objects cache might exist for months and is still valid
        $this->objectCache = $this->initCache($this->config->objects_file,$backendOptions,NULL);
        $this->statusCache = $this->initCache($this->config->status_file,$backendOptions,$maxCacheLifetime);

    }

    private function initCache($file, $backend, $lifetime)
    {
        $frontendOptions = array(
            'lifetime' => $lifetime,
            'automatic_serialization' => true,
            'master_files' => array($file)
        );
        return \Zend_Cache::factory('Core','File',$frontendOptions,$backend);
    }

    private function parseObjectsCacheFile()
    {
        if(!is_readable($this->config->objects_file))
            throw new \Icinga\Exception\ConfigurationError("Can't read objects-file {$this->config->objects_file}, check your configuration");
        if(!$this->parser)
            $this->parser = new Parser(fopen($this->config->objects_file,"r"));
        $this->parser->parseObjectsFile();
        $this->lastState = &$this->parser->getRuntimeState();
    }

    private function parseStatusDatFile()
    {
        if(!is_readable($this->config->status_file))
            throw new \Icinga\Exception\ConfigurationError("Can't read status-file {$this->config->status_file}, check your configuration");
        if(!$this->parser)
            $this->parser = new Parser(fopen($this->config->status_file,"r"),$this->lastState);
        $this->parser->parseRuntimeState(fopen($this->config->status_file,"r"));
        $this->lastState = &$this->parser->getRuntimeState();
        if(!$this->noCache)
            $this->statusCache->save(array("true" => true),"state".md5($this->config->objects_file));
    }


}
