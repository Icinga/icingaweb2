<?php

namespace Icinga\Module\Monitoring\Object;

use Icinga\Module\Monitoring\DataView\Contact;
use Icinga\Module\Monitoring\DataView\Contactgroup;
use Icinga\Module\Monitoring\DataView\Downtime;
use Icinga\Module\Monitoring\DataView\EventHistory;
use Icinga\Module\Monitoring\DataView\Hostgroup;
use Icinga\Module\Monitoring\DataView\Comment;
use Icinga\Module\Monitoring\DataView\Servicegroup;
use Icinga\Module\Monitoring\DataView\Customvar;
use Icinga\Web\Request;

abstract class AbstractObject
{
    const TYPE_HOST = 1;
    const TYPE_SERVICE = 2;

    public $type           = self::TYPE_HOST;
    public $prefix         = 'host_';

    public $comments       = array();
    public $downtimes      = array();
    public $hostgroups     = array();
    public $servicegroups  = array();
    public $contacts       = array();
    public $contactgroups  = array();
    public $customvars     = array();
    public $events         = array();

    private $properties = array();
    private $request    = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->properties = $this->getProperties();
    }

    abstract protected function getProperties();

    public function fetchComments()
    {
        $this->comments = Comment::fromRequest(
            $this->request,
            array(
                'comment_internal_id',
                'comment_timestamp',
                'comment_author',
                'comment_data',
                'comment_type',
            )
        )->getQuery()
            ->where('comment_objecttype_id', 1)

            ->fetchAll();

        return $this;
    }

    public function fetchDowntimes()
    {
        $this->downtimes = Downtime::fromRequest($this->request)->getQuery()->fetchAll();
        return $this;
    }

    public function fetchHostgroups()
    {
        $this->hostgroups = Hostgroup::fromRequest(
            $this->request,
            array(
                'hostgroup_name',
                'hostgroup_alias'
            )
        )->getQuery()->fetchPairs();

        return $this;
    }

    public function fetchCustomvars()
    {
        $query = Customvar::fromRequest(
            $this->request,
            array(
                'varname',
                'varvalue'
            )
        )->getQuery();

        if ($this->type === self::TYPE_HOST) {
            $query->where('host_name', $this->host_name)
                ->where('object_type', 'host');
        } else {
            $query->where('host_name', $this->host_name)
                ->where('object_type', 'service')
                ->where('service_description', $this->service_description);
        }

        $this->customvars = $query->fetchPairs();
        return $this;
    }

    public function fetchContacts()
    {
        $this->contacts = Contact::fromRequest(
            $this->request,
            array(
                'contact_name',
                'contact_alias',
                'contact_email',
                'contact_pager',
            )
        )->getQuery()
            ->where('host_name', $this->host_name)
            ->fetchAll();
        return $this;
    }

    public function fetchServicegroups()
    {
        $this->servicegroups = Servicegroup::fromRequest(
            $this->request,
            array(
                'servicegroup_name',
                'servicegroup_alias',
            )
        )->getQuery()->fetchPairs();
        return $this;
    }

    public function fetchContactgroups()
    {
        $this->contactgroups = Contactgroup::fromRequest(
            $this->request,
            array(
                'contactgroup_name',
                'contactgroup_alias'
            )
        )->getQuery()->fetchAll();

        return $this;
    }

    public function fetchEventHistory()
    {
        $this->eventhistory = EventHistory::fromRequest(
            $this->request,
            array(
                'object_type',
                'host_name',
                'service_description',
                'timestamp',
                'raw_timestamp',
                'state',
                'attempt',
                'max_attempts',
                'output',
                'type'
            )
        )->sort('timestamp', 'DESC')->getQuery();
        return $this;
    }

    public function __get($param)
    {

        if (isset($this->properties->$param)) {
            return $this->properties->$param;
        } elseif (isset($this->$param)) {
            return $this->$param;
        }
        if (substr($param, 0, strlen($this->prefix)) === $this->prefix) {
            return false;
        }
        $expandedName = $this->prefix . strtolower($param);
        return $this->$expandedName;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public static function fromRequest(Request $request)
    {
        if ($request->has('service') && $request->has('host')) {
            return new Service($request);
        } elseif ($request->has('host')) {
            return new Host($request);
        }
    }

    abstract public function populate();
}
