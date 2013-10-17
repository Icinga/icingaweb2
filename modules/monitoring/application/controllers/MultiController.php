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

use \Icinga\Web\Form;
use \Icinga\Module\Monitoring\Backend;
use \Icinga\Module\Monitoring\Object\Host;
use \Icinga\Module\Monitoring\Object\Service;
use \Icinga\Module\Monitoring\Form\Command\MultiCommandFlagForm;
use \Icinga\Module\Monitoring\DataView\HostAndServiceStatus as HostAndServiceStatusView;
use \Icinga\Module\Monitoring\DataView\Comment as CommentView;
use \Icinga\Web\Controller\ActionController;

/**
 * Displays aggregations collections of multiple objects.
 */
class Monitoring_MultiController extends ActionController
{
    public function init()
    {
        $this->view->queries = $this->getDetailQueries();
        $this->view->wildcard = false;
        $this->backend = Backend::createBackend($this->_getParam('backend'));
    }

    public function hostAction()
    {
        $queries = $this->view->queries;
        $hosts = array();
        $errors = array();

        if ($this->_getParam('host') === '*') {
            // fetch all hosts
            $hosts = HostAndServiceStatusView::fromRequest(
                $this->_request,
                array(
                    'host_name',
                    'host_in_downtime',
                    'host_accepts_passive_checks',
                    'host_does_active_checks',
                    'host_notifications_enabled',

                    // TODO: flags missing in HostAndServiceStatus:
                    'host_obsessing',
                    'host_event_handler_enabled',
                    'host_flap_detection_enabled'
                    // <<
                )
            )->getQuery()->fetchAll();
            $comments = array_keys($this->getUniqueValues(
                CommentView::fromRequest($this->_request)->getQuery()->fetchAll(),
                'comment_id'
            ));
        } else {
            // fetch specified hosts
            foreach ($queries as $index => $query) {
                if (!array_key_exists('host', $query)) {
                    $errors[] = 'Query ' . $index . ' misses property host.';
                    continue;
                }
                $hosts[] = Host::fetch($this->backend, $query['host']);
            }
        }
        $this->view->objects = $this->view->hosts = $hosts;
        $this->view->comments = isset($comments) ? $comments : $this->getComments($hosts);
        $this->view->hostnames = $this->getHostnames($hosts);
        $this->view->downtimes = $this->getDowntimes($hosts);
        $this->view->errors = $errors;

        $this->handleConfigurationForm();
        $this->view->form->setAction('/icinga2-web/monitoring/multi/host');
    }

    /**
     * Create an array with all unique values as keys.
     *
     * @param array $values     The array containing the objects
     * @param       $key        The key to access
     *
     * @return array
     */
    private function getUniqueValues(array $values, $key)
    {
        $unique = array();
        foreach ($values as $value)
        {
            $unique[$value->{$key}] = null;
        }
        return $unique;
    }

    private function getComments($objects)
    {
        $unique = array();
        foreach ($objects as $object) {
            $unique = array_merge($this->getUniqueValues($object->comments, 'comment_id'));
        }
        return array_keys($unique);
    }

    private function getHostnames($objects)
    {
        $objectnames = array();
        foreach ($objects as $object) {
            $objectnames[] = $object->host_name;
        }
        return $objectnames;
    }

    private function getDowntimes($objects)
    {
        $downtimes = array();
        foreach ($objects as $object)
        {
            if (
                isset($object->host_in_downtime) && $object->host_in_downtime ||
                isset($object->service_in_downtime) && $object->host_in_downtime
            ) {
                $downtimes[] = true;
            }
        }
        return $downtimes;
    }

    public function serviceAction()
    {
        $queries = $this->view->queries;
		$services = array();
        $errors = array();

        if ($this->_getParam('service') === '*' && $this->_getParam('host') === '*') {
            $services = HostAndServiceStatusView::fromRequest(
                $this->_request,
                array(
                    'host_name',
                    'service_name',
                    'service_in_downtime',
                    'service_accepts_passive_checks',
                    'service_does_active_checks',
                    'service_notifications_enabled',

                    // TODO: Flag misses in HostAndServiceStatus
                    'service_obsessing',
                    'service_event_handler_enabled',
                    'service_flap_detection_enabled'
                    // <<
                )
            )->getQuery()->fetchAll();
            $comments = array_keys($this->getUniqueValues(
                CommentView::fromRequest($this->_request)->getQuery()->fetchAll(),
                'comment_id'
            ));
        } else {
            // fetch specified hosts
            foreach ($queries as $index => $query) {
                if (!array_key_exists('host', $query)) {
				    $errors[] = 'Query ' . $index . ' misses property host.';
				    continue;
			    }
			    if (!array_key_exists('service', $query)) {
				    $errors[] = 'Query ' . $index . ' misses property service.';
				    continue;
			    }
                $services[] = Service::fetch($this->backend, $query['host'], $query['service']);
            }
        }
        $this->view->objects = $this->view->services = $services;
        $this->view->comments = isset($comments) ? $comments : $this->getComments($services);
        $this->view->hostnames = $this->getHostnames($services);
        $this->view->downtimes = $this->getDowntimes($services);
        $this->view->errors = $errors;

        $this->handleConfigurationForm();
        $this->view->form->setAction('/icinga2-web/monitoring/multi/service');
    }

    /**
     * Handle the form to configure configuration flags.
     */
    private function handleConfigurationForm()
    {
        $this->view->form = $form = new MultiCommandFlagForm(
            array(
                'passive_checks_enabled' => 'Passive Checks',
                'active_checks_enabled' => 'Active Checks',
                'obsessing' => 'Obsessing',
                'notifications_enabled' => 'Notifications',
                'event_handler_enabled' => 'Event Handler',
                'flap_detection_enabled' => 'Flap Detection'
            )
        );
        $form->setRequest($this->_request);
        if ($form->isSubmittedAndValid()) {
            // TODO: Handle commands
            $changed = $form->getChangedValues();
        }
        if ($this->_request->isPost() === false) {
            $this->view->form->initFromItems($this->view->objects);
        }
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
