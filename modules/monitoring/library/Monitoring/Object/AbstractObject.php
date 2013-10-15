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
 *
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Object;

use Icinga\Data\AbstractQuery as Query;
use \Icinga\Module\Monitoring\Backend;

/**
 * Generic icinga object with belongings
 */
abstract class AbstractObject
{
    protected $backend;

    protected $type;

    protected $name1;

    protected $name2;

    protected $properties;

    protected $foreign = array();

    public function __construct(Backend $backend, $name1, $name2 = null)
    {
        $this->backend = $backend;
        $this->name1 = $name1;
        $this->name2 = $name2;

        if ($name1 && $name2) {
            $this->type = 2;
        } elseif ($name1 && !$name2) {
            $this->type = 1;
        }

        $this->properties = (array) $this->fetchObject();
    }

    public static function fetch(Backend $backend, $name1, $name2 = null)
    {
        return new static($backend, $name1, $name2);
    }

    abstract protected function fetchObject();

    public function __isset($key)
    {
        return $this->$key !== null;
    }

    public function __get($key)
    {
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        }
        if (array_key_exists($key, $this->foreign)) {
            if ($this->foreign[$key] === null) {
                $func = 'fetch' . ucfirst($key);
                if (! method_exists($this, $func)) {
                    return null;
                }
                $this->$func($key);
            }
            return $this->foreign[$key];
        }
        return null;
    }

    public function prefetch()
    {
        return $this;
    }

    abstract protected function applyObjectFilter(Query $query);

    protected function fetchHostgroups()
    {
        $this->foreign['hostgroups'] = $this->applyObjectFilter(
            $this->backend->select()->from(
                'hostgroup',
                array(
                    'hostgroup_name',
                    'hostgroup_alias'
                )
            )
        )->fetchPairs();
        return $this;
    }

    protected function fetchServicegroups()
    {
        $this->foreign['servicegroups'] = $this->applyObjectFilter(
            $this->backend->select()->from(
                'servicegroup',
                array(
                    'servicegroup_name',
                    'servicegroup_alias'
                )
            )
        )->fetchPairs();
        return $this;
    }

    protected function fetchContacts()
    {
        $this->foreign['contacts'] = $this->applyObjectFilter(
            $this->backend->select()->from(
                'contact',
                array(
                    'contact_name',
                    'contact_alias',
                    'contact_email',
                    'contact_pager',
                )
            )
        )->fetchAll();
        return $this;
    }

    protected function fetchContactgroups()
    {
        $this->foreign['contactgroups'] = $this->applyObjectFilter(
            $this->backend->select()->from(
                'contactgroup',
                array(
                    'contactgroup_name',
                    'contactgroup_alias',
                )
            )
        )->fetchAll();
        return $this;
    }

    protected function fetchComments()
    {
        $this->foreign['comments'] = $this->applyObjectFilter(
            $this->backend->select()->from(
                'comment',
                array(
                    'comment_timestamp',
                    'comment_author',
                    'comment_data',
                    'comment_type',
                    'comment_internal_id'
                )
            )->where('comment_objecttype_id', $this->type)
        )->fetchAll();
        return $this;
    }

    protected function fetchCustomvars()
    {
        $this->foreign['customvars'] = $this->applyObjectFilter(
            $this->backend->select()->from(
                'customvar',
                array(
                    'varname',
                    'varvalue'
                )
            )->where('varname', '-*PW*,-*PASS*,-*COMMUNITY*')
        )->fetchPairs();
        return $this;
    }

    public function fetchEventHistory()
    {
        $this->foreign['eventHistory'] = $this->applyObjectFilter(
            $this->backend->select()->from(
                'eventHistory',
                array(
                    'object_type',
                    'host_name',
                    'service_description',
                    'timestamp',
                    'state',
                    'attempt',
                    'max_attempts',
                    'output',
                    'type'
                )
            )
        );
        return $this;
    }

    public function fetchDowtimes()
    {
        $this->foreign['downtimes'] = $this->applyObjectFilter(
            $this->backend->select()->from(
                'downtime',
                array(
                    'host_name',
                    'object_type',
                    'service_host_name',
                    'service_description',
                    'downtime_type',
                    'downtime_author_name',
                    'downtime_comment_data',
                    'downtime_is_fixed',
                    'downtime_duration',
                    'downtime_entry_time',
                    'downtime_scheduled_start_time',
                    'downtime_scheduled_end_time',
                    'downtime_was_started',
                    'downtime_actual_start_time',
                    'downtime_actual_start_time_usec',
                    'downtime_is_in_effect',
                    'downtime_trigger_time',
                    'downtime_triggered_by_id',
                    'downtime_internal_downtime_id'
                )
            )
        )->fetchAll(9);
        return $this;
    }
}
