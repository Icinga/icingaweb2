<?php

namespace Icinga\Backend;

use Icinga\Protocol\Statusdat as StatusdatProtocol;


class Statusdat extends AbstractBackend
{
    private $reader = null;

    public function init()
    {
        $this->reader = new StatusdatProtocol\Reader($this->config);
    }

    public function getReader()
    {
        return $this->reader;
    }

    public function listServices($filter = array(), $flags = array())
    {
        $query = $this->select()->from("servicelist");
        return $query->fetchAll();
    }


    public function fetchHost($host)
    {
        $objs = & $this->reader->getObjects();

        if (!isset($objs["host"][$host]))
            return null;
        $result = array($objs["host"][$host]);
        return new MonitoringObjectList(
            $result,
            new \Icinga\Backend\Statusdat\DataView\StatusdatHostView($this->reader)
        );
    }

    public function fetchService($host, $service)
    {
        $idxName = $host . ";" . $service;
        $objs = & $this->reader->getObjects();

        if (!isset($objs["service"][$idxName]))
            return null;
        $result = array($objs["service"][$idxName]);
        return new MonitoringObjectList(
            $result,
            new \Icinga\Backend\Statusdat\DataView\StatusdatServiceView($this->reader)
        );

    }


}
