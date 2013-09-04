<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 * 
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat;


use Icinga\Data\DatasourceInterface;
use Icinga\Exception;
use Icinga\Benchmark;
use Icinga\Protocol\Statusdat\View\MonitoringObjectList;

/**
 * Class Reader
 * @package Icinga\Protocol\Statusdat
 */
class Reader implements IReader, DatasourceInterface
{
    /**
     *
     */
    const DEFAULT_CACHE_LIFETIME = 300;

    /**
     *
     */
    const STATUSDAT_DEFAULT_CACHE_PATH = "/cache";

    /**
     * @var null
     */
    private $lastState = null;

    /**
     * @var bool
     */
    private $hasRuntimeState = false;

    /**
     * @var null
     */
    private $objectCache = null;

    /**
     * @var null
     */
    private $statusCache = null;

    /**
     * @var bool
     */
    private $newState = false;

    /**
     * @var null
     */
    private $parser = null;

    /**
     * @var bool
     */
    private $noCache = false;

    /**
     * @param $config
     * @param null $parser
     * @param bool $noCache
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
            $this->statusCache->save($this->parser->getRuntimeState(), 'objects' . md5($this->config->objects_file));
        }
        $this->createHostServiceConnections();

    }

    /**
     * @throws Exception\ConfigurationError
     */
    private function initializeCaches()
    {
        $defaultCachePath = "/tmp" . self::STATUSDAT_DEFAULT_CACHE_PATH;

        $cachePath = $this->config->get('cache_path', $defaultCachePath);
        $maxCacheLifetime = intval($this->config->get('cache_path', self::DEFAULT_CACHE_LIFETIME));
        if (!is_writeable($cachePath)) {
            throw new Exception\ConfigurationError(
                "Cache path $cachePath is not writable, check your configuration"
            );
        }


        $backendOptions = array(
            'cache_dir' => $cachePath
        );
        // the objects cache might exist for months and is still valid
        $this->objectCache = $this->initCache($this->config->objects_file, $backendOptions, null);
        $this->statusCache = $this->initCache($this->config->status_file, $backendOptions, $maxCacheLifetime);

    }

    /**
     * @param $file
     * @param $backend
     * @param $lifetime
     * @return \Zend_Cache_Core|\Zend_Cache_Frontend
     */
    private function initCache($file, $backend, $lifetime)
    {
        $frontendOptions = array(
            'lifetime' => $lifetime,
            'automatic_serialization' => true,
            'master_files' => array($file)
        );
        return \Zend_Cache::factory('Core', 'File', $frontendOptions, $backend);
    }

    /**
     * @return bool
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
     * @return bool
     */
    private function readObjectsCache()
    {
        $this->lastState = $this->objectCache->load('objects' . md5($this->config->objects_file));
        if ($this->lastState == false) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
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
     *
     */
    private function createHostServiceConnections()
    {
        if (!isset($this->lastState["service"])) {
            return;
        }

        foreach ($this->lastState["service"] as &$service) {
            $host = & $this->lastState["host"][$service->host_name];
            if (!isset($host->services)) {
                $host->services = array();
            }
            $host->services[$service->service_description] = & $service;
            $service->host = & $host;
        }
    }

    /**
     * @throws Exception\ConfigurationError
     */
    private function parseObjectsCacheFile()
    {
        if (!is_readable($this->config->objects_file)) {
            throw new Exception\ConfigurationError(
                "Can't read objects-file {$this->config->objects_file}, check your configuration"
            );
        }
        if (!$this->parser) {
            $this->parser = new Parser(fopen($this->config->objects_file, "r"));
        }
        $this->parser->parseObjectsFile();
        $this->lastState = $this->parser->getRuntimeState();
    }

    /**
     * @throws Exception\ConfigurationError
     */
    private function parseStatusDatFile()
    {
        if (!is_readable($this->config->status_file)) {
            throw new Exception\ConfigurationError(
                "Can't read status-file {$this->config->status_file}, check your configuration"
            );
        }
        if (!$this->parser) {
            $this->parser = new Parser(fopen($this->config->status_file, "r"), $this->lastState);
        }
        $this->parser->parseRuntimeState(fopen($this->config->status_file, "r"));
        $this->lastState = $this->parser->getRuntimeState();
        if (!$this->noCache) {
            $this->statusCache->save(array("true" => true), "state" . md5($this->config->objects_file));
        }
    }

    /**
     * @return Query
     */
    public function select()
    {
        return new Query($this);
    }

    /**
     * @param Query $query
     * @return MonitoringObjectList
     */
    public function fetchAll(Query $query)
    {
        return $query->getResult();
    }

    /**
     * @return mixed|null
     */
    public function getState()
    {
        return $this->lastState;
    }

    /**
     * @return mixed|null
     */
    public function getObjects()
    {
        return $this->lastState;
    }

    /**
     * @param $type
     * @param $name
     * @return ObjectContainer|mixed|null
     */
    public function getObjectByName($type, $name)
    {
        if (isset($this->lastState[$type]) && isset($this->lastState[$type][$name])) {
            return new ObjectContainer($this->lastState[$type][$name], $this);
        }
        return null;
    }

    /**
     * @param $type
     * @return array|null
     */
    public function getObjectNames($type)
    {
        return isset($this->lastState[$type]) ? array_keys($this->lastState[$type]) : null;
    }
}
