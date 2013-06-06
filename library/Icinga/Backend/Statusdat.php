<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Backend;

use Icinga\Protocol\Statusdat as StatusdatProtocol;

/**
 * Class Statusdat
 * @package Icinga\Backend
 */
class Statusdat extends AbstractBackend
{
    /**
     * @var null
     */
    private $reader = null;

    /**
     *
     */
    public function init()
    {
        $this->reader = new StatusdatProtocol\Reader($this->config);
    }

    /**
     * @return null
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * @param array $filter
     * @param array $flags
     * @return mixed
     */
    public function listServices($filter = array(), $flags = array())
    {
        $query = $this->select()->from("servicelist");
        return $query->fetchAll();
    }

    /**
     * @param $host
     * @return MonitoringObjectList|null
     */
    public function fetchHost($host)
    {
        $objs = & $this->reader->getObjects();

        if (!isset($objs["host"][$host])) {
            return null;
        }
        $result = array($objs["host"][$host]);
        return new MonitoringObjectList(
            $result,
            new \Icinga\Backend\Statusdat\DataView\StatusdatHostView($this->reader)
        );
    }

    /**
     * @param $host
     * @param $service
     * @return MonitoringObjectList|null
     */
    public function fetchService($host, $service)
    {
        $idxName = $host . ";" . $service;
        $objs = & $this->reader->getObjects();

        if (!isset($objs["service"][$idxName])) {
            return null;
        }
        $result = array($objs["service"][$idxName]);
        return new MonitoringObjectList(
            $result,
            new \Icinga\Backend\Statusdat\DataView\StatusdatServiceView($this->reader)
        );

    }
}
