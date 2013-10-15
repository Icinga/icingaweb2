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

use \Icinga\Web\Controller\ActionController;
use \Icinga\Module\Monitoring\Backend;
use \Icinga\Module\Monitoring\Object\Host;
use \Icinga\Module\Monitoring\Object\Service;
/**
 * Displays aggregations collections of multiple objects.
 */
class Monitoring_MultiController extends ActionController
{
    public function init()
    {
        $this->view->queries = $this->getDetailQueries();
        $this->backend = Backend::createBackend($this->_getParam('backend'));
    }

    public function hostAction()
    {
        $hosts = array();
        $hostnames = array();
        $comments = array();
        $downtimes = array();

        $errors = array();

        foreach ($this->view->queries as $index => $query) {
            if (!array_key_exists('host', $query)) {
                $errors[] = 'Query ' . $index . ' misses property host.';
                continue;
            }

            $host = Host::fetch($this->backend, $query['host']);
            foreach ($host->comments as $comment) {
                $comments[$comment->comment_id] = null;
            }
            if ($host->host_in_downtime) {
                $downtimes[] += $host->host_in_downtime;
            }
            $hostnames[] = $host->host_name;
            $hosts[] = $host;
        }

        $this->view->objects = $this->view->hosts = $hosts;
        $this->view->comments = array_keys($comments);
        $this->view->hostnames = $hostnames;
        $this->view->downtimes = $downtimes;
        $this->view->errors = $errors;
    }

    public function serviceAction()
    {
		$services = array();
        $comments = array();
        $downtimes = array();

        $errors = array();

        foreach ($this->view->queries as $index => $query) {
			if (!array_key_exists('host', $query)) {
				$errors[] = 'Query ' . $index . ' misses property host.';
				continue;
			}
			if (!array_key_exists('service', $query)) {
				$errors[] = 'Query ' . $index . ' misses property service.';
				continue;
			}

            $service = Service::fetch($this->backend, $query['host'], $query['service']);
            foreach ($service->comments as $comment) {
                $comments[$comment->comment_id] = null;
            }
            if ($service->service_in_downtime) {
                $downtimes[] += $service->service_in_downtime;
            }
            $hostnames[] = $service->host_name;
            $services[] = $service;
        }
        $this->view->objects = $this->view->services = $services;
        $this->view->comments = array_keys($comments);
        $this->view->downtimes = $downtimes;
        $this->view->errors = $errors;
    }

    public function notificationAction()
    {

    }

    public function historyAction()
    {

    }

    /**
     * Fetch all requests from the 'detail' parameter.
     *
     * @return array    An array of request that contain
     *                  the filter arguments as properties.
     */
    private function getDetailQueries()
    {
        $details = $this->_getAllParams();
        $objects = array();
        foreach ($details as $property => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $index => $value) {
                if (!array_key_exists($index, $objects)) {
                    $objects[$index] = array();
                }
                $objects[$index][$property] = $value;
            }
        }
        return $objects;
    }


}
