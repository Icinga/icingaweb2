<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Object;

use InvalidArgumentException;
use Icinga\Application\Config;
use Icinga\Exception\InvalidPropertyException;
use Icinga\Module\Monitoring\Backend;
use Icinga\Web\UrlParams;

/**
 * A monitored Icinga object, i.e. host or service
 */
abstract class MonitoredObject
{
    /**
     * Type host
     */
    const TYPE_HOST = 'host';

    /**
     * Type service
     */
    const TYPE_SERVICE = 'service';

    /**
     * Backend to fetch object information from
     *
     * @var Backend
     */
    protected $backend;

    /**
     * Type of the Icinga object, i.e. 'host' or 'service'
     *
     * @var string
     */
    protected $type;

    /**
     * Prefix of the Icinga object, i.e. 'host_' or 'service_'
     *
     * @var string
     */
    protected $prefix;

    /**
     * Properties
     *
     * @var object
     */
    protected $properties;

    /**
     * Comments
     *
     * @var array
     */
    protected $comments;

    /**
     * Downtimes
     *
     * @var array
     */
    protected $downtimes;

    /**
     * Host groups
     *
     * @var array
     */
    protected $hostgroups;

    /**
     * Service groups
     *
     * @var array
     */
    protected $servicegroups;

    /**
     * Contacts
     *
     * @var array
     */
    protected $contacts;

    /**
     * Contact groups
     *
     * @var array
     */
    protected $contactgroups;

    /**
     * Custom variables
     *
     * @var array
     */
    protected $customvars;

    /**
     * Event history
     *
     * @var \Icinga\Module\Monitoring\DataView\EventHistory
     */
    protected $eventhistory;

    /**
     * Stats
     *
     * @var object
     */
    protected $stats;

    /**
     * Create a monitored object, i.e. host or service
     *
     * @param Backend $backend Backend to fetch object information from
     */
    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Get the object's data view
     *
     * @return \Icinga\Module\Monitoring\DataView\DataView
     */
    abstract protected function getDataView();

    /**
     * Fetch the object's properties
     *
     * @return bool
     */
    public function fetch()
    {
        $this->properties = $this->getDataView()->getQuery()->fetchRow();
        return $this->properties !== false;
    }

    /**
     * Get the type of the object
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Require the object's type to be one of the given types
     *
     * @param   array $oneOf
     *
     * @return  bool
     * @throws  InvalidArgumentException If the object's type is not one of the given types.
     */
    public function assertOneOf(array $oneOf)
    {
        if (! in_array($this->type, $oneOf)) {
            throw new InvalidArgumentException;
        }
        return true;
    }

    /**
     * Set the object's properties
     *
     * @param   object $properties
     *
     * @return  $this
     */
    public function setProperties($properties)
    {
        $this->properties = (object) $properties;
        return $this;
    }

    /**
     * Fetch the object's comments
     *
     * @return $this
     */
    public function fetchComments()
    {
        $comments = $this->backend->select()->from('comment', array(
            'id'        => 'comment_internal_id',
            'timestamp' => 'comment_timestamp',
            'author'    => 'comment_author',
            'comment'   => 'comment_data',
            'type'      => 'comment_type',
        ))
            ->where('comment_type', array('comment', 'ack'))
            ->where('comment_objecttype', $this->type)
            ->where('comment_host', $this->host_name);
        if ($this->type === self::TYPE_SERVICE) {
            $comments->where('comment_service', $this->service_description);
        }
        $this->comments = $comments->getQuery()->fetchAll();
        return $this;
    }

    /**
     * Fetch the object's downtimes
     *
     * @return $this
     */
    public function fetchDowntimes()
    {
        $downtimes = $this->backend->select()->from('downtime', array(
            'id'                => 'downtime_internal_id',
            'objecttype'        => 'downtime_objecttype',
            'comment'           => 'downtime_comment',
            'author'            => 'downtime_author',
            'start'             => 'downtime_start',
            'scheduled_start'   => 'downtime_scheduled_start',
            'end'               => 'downtime_end',
            'duration'          => 'downtime_duration',
            'is_flexible'       => 'downtime_is_flexible',
            'is_fixed'          => 'downtime_is_fixed',
            'is_in_effect'      => 'downtime_is_in_effect',
            'entry_time'        => 'downtime_entry_time',
            'host'              => 'downtime_host',
            'service'           => 'downtime_service'
        ))
            ->where('downtime_objecttype', $this->type)
            ->where('downtime_host', $this->host_name)
            ->order('downtime_is_in_effect', 'DESC')
            ->order('downtime_scheduled_start', 'ASC');
        if ($this->type === self::TYPE_SERVICE) {
            $downtimes->where('downtime_service', $this->service_description);
        }
        $this->downtimes = $downtimes->getQuery()->fetchAll();
        return $this;
    }

    /**
     * Fetch the object's host groups
     *
     * @return $this
     */
    public function fetchHostgroups()
    {
        $hostGroups = $this->backend->select()->from('hostgroup', array(
            'hostgroup_name',
            'hostgroup_alias'
        ))
            ->where('host_name', $this->host);
        $this->hostgroups = $hostGroups->getQuery()->fetchPairs();
        return $this;
    }

    /**
     * Fetch the object's custom variables
     *
     * @return $this
     */
    public function fetchCustomvars()
    {
        $blacklist = array();
        $blacklistPattern = '/^(.*pw.*|.*pass.*|community)$/i';

        if ($security = Config::module('monitoring')->get('security')) {

            $blacklistConfig = $security->get('protected_customvars', '');

            foreach (explode(',', $blacklistConfig) as $customvar) {
                $nonWildcards = array();
                foreach (explode('*', $customvar) as $nonWildcard) {
                    $nonWildcards[] = preg_quote($nonWildcard, '/');
                }
                $blacklist[] = implode('.*', $nonWildcards);
            }
            $blacklistPattern = '/^(' . implode('|', $blacklist) . ')$/i';
        }

        $query = $this->backend->select()->from('customvar', array(
            'varname',
            'varvalue'
        ))
            ->where('object_type', $this->type)
            ->where('host_name', $this->host_name);
        if ($this->type === self::TYPE_SERVICE) {
            $query->where('service_description', $this->service_description);
        }

        $this->customvars = array();

        $customvars = $query->getQuery()->fetchPairs();
        foreach ($customvars as $name => $value) {
            $name = ucwords(str_replace('_', ' ', strtolower($name)));
            if ($blacklistPattern && preg_match($blacklistPattern, $name)) {
                $value = '***';
            }
            $this->customvars[$name] = $value;
        }

        return $this;
    }

    /**
     * Fetch the object's contacts
     *
     * @return $this
     */
    public function fetchContacts()
    {
        $contacts = $this->backend->select()->from('contact', array(
                'contact_name',
                'contact_alias',
                'contact_email',
                'contact_pager',
        ))
            ->where('host_name', $this->host_name);
        if ($this->type === self::TYPE_SERVICE) {
            $contacts->where('service_description', $this->service_description);
        }
        $this->contacts = $contacts->getQuery()->fetchAll();
        return $this;
    }

    /**
     * Fetch the object's service groups
     *
     * @return $this
     */
    public function fetchServicegroups()
    {
        $serviceGroups = $this->backend->select()->from('servicegroup', array(
                'servicegroup_name',
                'servicegroup_alias'
        ))
            ->where('service_host_name', $this->host_name)
            ->where('service_description', $this->service_description);
        $this->servicegroups = $serviceGroups->getQuery()->fetchPairs();
        return $this;
    }

    /**
     * Fetch the object's contact groups
     *
     * @return $this
     */
    public function fetchContactgroups()
    {
        $contactsGroups = $this->backend->select()->from('contactgroup', array(
                'contactgroup_name',
                'contactgroup_alias'
        ))
            ->where('host_name', $this->host_name);
        if ($this->type === self::TYPE_SERVICE) {
            $contactsGroups->where('service_description', $this->service_description);
        }
        $this->contactgroups = $contactsGroups->getQuery()->fetchAll();
        return $this;
    }

    /**
     * Fetch the object's event history
     *
     * @return $this
     */
    public function fetchEventhistory()
    {
        $eventHistory = $this->backend->select()->from('eventHistory', array(
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
            ->order('timestamp', 'DESC')
            ->where('host_name', $this->host_name);
        if ($this->type === self::TYPE_SERVICE) {
            $eventHistory->where('service_description', $this->service_description);
        }
        $this->eventhistory = $eventHistory->getQuery();
        return $this;
    }

    /**
     * Fetch stats
     *
     * @return $this
     */
    public function fetchStats()
    {
        $this->stats = $this->backend->select()->from('statusSummary', array(
            'services_total',
            'services_ok',
            'services_problem',
            'services_problem_handled',
            'services_problem_unhandled',
            'services_critical',
            'services_critical_unhandled',
            'services_critical_handled',
            'services_warning',
            'services_warning_unhandled',
            'services_warning_handled',
            'services_unknown',
            'services_unknown_unhandled',
            'services_unknown_handled',
            'services_pending',
        ))
            ->where('service_host_name', $this->host_name)
            ->getQuery()
            ->fetchRow();
        return $this;
    }

    /**
     * Fetch all available data of the object
     *
     * @return $this
     */
    public function populate()
    {
        $this
            ->fetchComments()
            ->fetchContacts()
            ->fetchContactgroups()
            ->fetchCustomvars()
            ->fetchDowntimes();
        // Call fetchHostgroups or fetchServicegroups depending on the object's type
        $fetchGroups = 'fetch' . ucfirst($this->type) . 'groups';
        $this->$fetchGroups();
        return $this;
    }

    public function __get($name)
    {
        if (property_exists($this->properties, $name)) {
            return $this->properties->$name;
        } elseif (property_exists($this, $name) && $this->$name !== null) {
            return $this->$name;
        } elseif (property_exists($this, $name)) {
            $fetchMethod = 'fetch' . ucfirst($name);
            $this->$fetchMethod();
            return $this->$name;
        }
        if (substr($name, 0, strlen($this->prefix)) !== $this->prefix) {
            $prefixedName = $this->prefix . strtolower($name);
            if (property_exists($this->properties, $prefixedName)) {
                return $this->properties->$prefixedName;
            }
        }
        throw new InvalidPropertyException('Can\'t access property \'%s\'. Property does not exist.', $name);
    }

    public function __isset($name)
    {
        if (property_exists($this->properties, $name)) {
            return isset($this->properties->$name);
        } elseif (property_exists($this, $name)) {
            return isset($this->$name);
        }
        return false;
    }

    /**
     * @deprecated
     */
    public static function fromParams(UrlParams $params)
    {
        if ($params->has('service') && $params->has('host')) {
            return new Service(Backend::createBackend(), $params->get('host'), $params->get('service'));
        } elseif ($params->has('host')) {
            return new Host(Backend::createBackend(), $params->get('host'));
        }
        return null;
    }
}
