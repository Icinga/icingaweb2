<?php

namespace Icinga\Module\Monitoring\Object;

use Icinga\Data\AbstractQuery as Query;
use \Icinga\Module\Monitoring\Backend;

abstract class AbstractObject
{
    protected $backend;

    protected $type;

    protected $name1;

    protected $name2;

    protected $properties;

    protected $foreign = array(
        // 'hostgroups'    => null,
        // 'contacts'      => null,
        // 'contactgroups' => null,
        // 'servicegroups' => null,
        // 'customvars'    => null,
        // 'comments'      => null,
    );

    public function __construct(Backend $backend, $name1, $name2 = null)
    {
        $this->backend = $backend;
        $this->name1   = $name1;
        $this->name2   = $name2;
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
            $this->backend->select()->from('hostgroup', array(
                'hostgroup_name',
                'hostgroup_alias'
            ))
        )->fetchPairs();
        return $this;
    }

    protected function fetchServicegroups()
    {
        $this->foreign['servicegroups'] = $this->applyObjectFilter(
            $this->backend->select()->from('servicegroup', array(
                'servicegroup_name',
                'servicegroup_alias'
            ))
        )->fetchPairs();
        return $this;
    }

    protected function fetchContacts()
    {
        $this->foreign['contacts'] = $this->applyObjectFilter(
            $this->backend->select()->from('contact', array(
                'contact_name',
                'contact_alias',
                'contact_email',
                'contact_pager',
            ))
        )->fetchAll();
        return $this;
    }

    protected function fetchContactgroups()
    {
        $this->foreign['contactgroups'] = $this->applyObjectFilter(
            $this->backend->select()->from('contactgroup', array(
                'contactgroup_name',
                'contactgroup_alias',
            ))
        )->fetchAll();
        return $this;
    }

    protected function fetchComments()
    {
        $this->foreign['comments'] = $this->applyObjectFilter(
            $this->backend->select()->from('comment', array(
                'comment_timestamp',
                'comment_author',
                'comment_data',
                'comment_type',
            ))
        )->fetchAll();
        return $this;
    }

    protected function fetchCustomvars()
    {
        $this->foreign['customvars'] = $this->applyObjectFilter(
            $this->backend->select()->from('customvar', array(
                'varname',
                'varvalue'
            ))
            ->where('varname', '-*PW*,-*PASS*,-*COMMUNITY*')
            ->where('object_type', 'host')
        )->fetchPairs();
        return $this;
    }

    protected function fetchEventHisoty()
    {
        $this->foreign['eventHistory'] = $this->applyObjectFilter(
            $this->backend->select()->from('eventHistory', array(
                'object_type',
                'host_name',
                'service_description',
                'timestamp',
                'state',
                'attempt',
                'max_attempts',
                'output',
                'type'
            ))
        );
        return $this;
    }
}
