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

namespace Icinga\Module\Monitoring\Backend;


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
    public function fetchHost($host, $fetchAll = false)
    {
        $objs = & $this->reader->getObjects();

        if (!isset($objs["host"][$host])) {
            return null;
        }
        $result = array($objs["host"][$host]);
        return new MonitoringObjectList(
            $result,
            new StatusdatHostView($this->reader)
        );
    }

    /**
     * @param $host
     * @param $service
     * @return MonitoringObjectList|null
     */
    public function fetchService($host, $service, $fetchAll = false)
    {
        $idxName = $host . ";" . $service;
        $objs = & $this->reader->getObjects();

        if (!isset($objs["service"][$idxName])) {
            return null;
        }
        $result = array($objs["service"][$idxName]);
        return new MonitoringObjectList(
            $result,
            new StatusdatServiceView($this->reader)
        );

    }
}
