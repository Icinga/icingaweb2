<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

use stdClass;
use InvalidArgumentException;
use Icinga\Authentication\Auth;
use Icinga\Application\Config;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filterable;
use Icinga\Exception\InvalidPropertyException;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Util\GlobFilter;
use Icinga\Web\UrlParams;

/**
 * A monitored Icinga object, i.e. host or service
 */
abstract class MonitoredObject implements Filterable
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
     * Acknowledgement of the host or service if any
     *
     * @var object
     */
    protected $acknowledgement;

    /**
     * Backend to fetch object information from
     *
     * @var MonitoringBackend
     */
    protected $backend;

    /**
     * Comments
     *
     * @var array
     */
    protected $comments;

    /**
     * This object's obfuscated custom variables
     *
     * @var array
     */
    protected $customvars;

    /**
     * The host custom variables
     *
     * @var array
     */
    protected $hostVariables;

    /**
     * The service custom variables
     *
     * @var array
     */
    protected $serviceVariables;

    /**
     * Contact groups
     *
     * @var array
     */
    protected $contactgroups;

    /**
     * Contacts
     *
     * @var array
     */
    protected $contacts;

    /**
     * Downtimes
     *
     * @var array
     */
    protected $downtimes;

    /**
     * Event history
     *
     * @var \Icinga\Module\Monitoring\DataView\EventHistory
     */
    protected $eventhistory;

    /**
     * Filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * Host groups
     *
     * @var array
     */
    protected $hostgroups;

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
     * Service groups
     *
     * @var array
     */
    protected $servicegroups;

    /**
     * Type of the Icinga object, i.e. 'host' or 'service'
     *
     * @var string
     */
    protected $type;

    /**
     * Stats
     *
     * @var object
     */
    protected $stats;

    /**
     * The properties to hide from the user
     *
     * @var GlobFilter
     */
    protected $blacklistedProperties = null;

    /**
     * Create a monitored object, i.e. host or service
     *
     * @param MonitoringBackend $backend Backend to fetch object information from
     */
    public function __construct(MonitoringBackend $backend)
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
     * Get all note urls configured for this monitored object
     *
     * @return array All note urls as a string
     */
    public abstract function getNotesUrls();

    /**
     * {@inheritdoc}
     */
    public function addFilter(Filter $filter)
    {
        // Left out on purpose. Interface is deprecated.
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilter(Filter $filter)
    {
        $this->getFilter()->addFilter($filter);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilter()
    {
        if ($this->filter === null) {
            $this->filter = Filter::matchAll();
        }

        return $this->filter;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilter(Filter $filter)
    {
        // Left out on purpose. Interface is deprecated.
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, $value = null)
    {
        // Left out on purpose. Interface is deprecated.
    }

    /**
     * Return whether this object matches the given filter
     *
     * @param   Filter  $filter
     *
     * @return  bool
     *
     * @throws  ProgrammingError    In case the object cannot be found
     *
     * @deprecated      Use $filter->matches($object) instead
     */
    public function matches(Filter $filter)
    {
        if ($this->properties === null && $this->fetch() === false) {
            throw new ProgrammingError(
                'Unable to apply filter. Object %s of type %s not found.',
                $this->getName(),
                $this->getType()
            );
        }

        return $filter->matches($this);
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
     * Fetch the object's properties
     *
     * @return bool
     */
    public function fetch()
    {
        $properties = $this->getDataView()->applyFilter($this->getFilter())->getQuery()->fetchRow();

        if ($properties === false) {
            return false;
        }

        if (isset($properties->host_contacts)) {
            $this->contacts = array();
            foreach (preg_split('~,~', $properties->host_contacts) as $contact) {
                $this->contacts[] = (object) array(
                    'contact_name'  => $contact,
                    'contact_alias' => $contact,
                    'contact_email' => null,
                    'contact_pager' => null,
                );
            }
        }

        $this->properties = $properties;

        return true;
    }

    /**
     * Fetch the object's acknowledgement
     */
    public function fetchAcknowledgement()
    {
        if ($this->comments === null) {
            $this->fetchComments();
        }

        return $this;
    }

    /**
     * Fetch the object's comments
     *
     * @return $this
     */
    public function fetchComments()
    {
        if ($this->backend->is('livestatus')) {
            $this->comments = array();
            return $this;
        }

        $commentsView = $this->backend->select()->from('comment', array(
            'author'            => 'comment_author_name',
            'comment'           => 'comment_data',
            'expiration'        => 'comment_expiration',
            'id'                => 'comment_internal_id',
            'name'              => 'comment_name',
            'persistent'        => 'comment_is_persistent',
            'timestamp'         => 'comment_timestamp',
            'type'              => 'comment_type'
        ));
        if ($this->type === self::TYPE_SERVICE) {
            $commentsView
                ->where('service_host_name', $this->host_name)
                ->where('service_description', $this->service_description);
        } else {
            $commentsView->where('host_name', $this->host_name);
        }
        $commentsView
            ->where('comment_type', array('ack', 'comment'))
            ->where('object_type', $this->type);

        $comments = $commentsView->fetchAll();

        if ((bool) $this->properties->{$this->prefix . 'acknowledged'}) {
            $ackCommentIdx = null;

            foreach ($comments as $i => $comment) {
                if ($comment->type === 'ack') {
                    $this->acknowledgement = new Acknowledgement(array(
                        'author'            => $comment->author,
                        'comment'           => $comment->comment,
                        'entry_time'        => $comment->timestamp,
                        'expiration_time'   => $comment->expiration,
                        'sticky'            => (int) $this->properties->{$this->prefix . 'acknowledgement_type'} === 2
                    ));
                    $ackCommentIdx = $i;
                    break;
                }
            }

            if ($ackCommentIdx !== null) {
                unset($comments[$ackCommentIdx]);
            }
        }

        $this->comments = $comments;

        return $this;
    }

    /**
     * Fetch the object's contact groups
     *
     * @return $this
     */
    public function fetchContactgroups()
    {
        if ($this->backend->is('livestatus')) {
            $this->contactgroups = array();
            return $this;
        }

        $contactsGroups = $this->backend->select()->from('contactgroup', array(
            'contactgroup_name',
            'contactgroup_alias'
        ));
        if ($this->type === self::TYPE_SERVICE) {
            $contactsGroups
                ->where('service_host_name', $this->host_name)
                ->where('service_description', $this->service_description);
        } else {
            $contactsGroups->where('host_name', $this->host_name);
        }
        $this->contactgroups = $contactsGroups;
        return $this;
    }

    /**
     * Fetch the object's contacts
     *
     * @return $this
     */
    public function fetchContacts()
    {
        if ($this->backend->is('livestatus')) {
            $this->contacts = array();
            return $this;
        }

        $contacts = $this->backend->select()->from('contact', array(
            'contact_name',
            'contact_alias',
            'contact_email',
            'contact_pager',
        ));
        if ($this->type === self::TYPE_SERVICE) {
            $contacts
                ->where('service_host_name', $this->host_name)
                ->where('service_description', $this->service_description);
        } else {
            $contacts->where('host_name', $this->host_name);
        }
        $this->contacts = $contacts;
        return $this;
    }

    /**
     * Fetch this object's obfuscated custom variables
     *
     * @return  $this
     */
    public function fetchCustomvars()
    {
        if ($this->backend->is('livestatus')) {
            $this->customvars = array();
            return $this;
        }

        $blacklist = array();
        $blacklistPattern = '';

        if (($blacklistConfig = Config::module('monitoring')->get('security', 'protected_customvars', '')) !== '') {
            foreach (explode(',', $blacklistConfig) as $customvar) {
                $nonWildcards = array();
                foreach (explode('*', $customvar) as $nonWildcard) {
                    $nonWildcards[] = preg_quote($nonWildcard, '/');
                }
                $blacklist[] = implode('.*', $nonWildcards);
            }
            $blacklistPattern = '/^(' . implode('|', $blacklist) . ')$/i';
        }

        if ($this->type === self::TYPE_SERVICE) {
            $this->fetchServiceVariables();
            $customvars = $this->serviceVariables;
        } else {
            $this->fetchHostVariables();
            $customvars = $this->hostVariables;
        }

        $this->customvars = $customvars;
        $this->hideBlacklistedProperties();

        if ($blacklistPattern) {
            $this->customvars = $this->obfuscateCustomVars($this->customvars, $blacklistPattern);
        }

        return $this;
    }

    /**
     * Obfuscate custom variables recursively
     *
     * @param stdClass|array    $customvars         The custom variables to obfuscate
     * @param string            $blacklistPattern   Which custom variables to obfuscate
     *
     * @return stdClass|array                       The obfuscated custom variables
     */
    protected function obfuscateCustomVars($customvars, $blacklistPattern)
    {
        $obfuscatedCustomVars = array();
        foreach ($customvars as $name => $value) {
            if ($blacklistPattern && preg_match($blacklistPattern, $name)) {
                $obfuscatedCustomVars[$name] = '***';
            } else {
                $obfuscatedCustomVars[$name] = $value instanceof stdClass || is_array($value)
                    ? $this->obfuscateCustomVars($value, $blacklistPattern)
                    : $value;
            }
        }
        return $customvars instanceof stdClass ? (object) $obfuscatedCustomVars : $obfuscatedCustomVars;
    }

    /**
     * Hide all blacklisted properties from the user as restricted by monitoring/blacklist/properties
     *
     * Currently this only affects the custom variables
     */
    protected function hideBlacklistedProperties()
    {
        if ($this->blacklistedProperties === null) {
            $this->blacklistedProperties = new GlobFilter(
                Auth::getInstance()->getRestrictions('monitoring/blacklist/properties')
            );
        }

        $allProperties = $this->blacklistedProperties->removeMatching(
            array($this->type => array('vars' => $this->customvars))
        );
        $this->customvars = isset($allProperties[$this->type]['vars']) ? $allProperties[$this->type]['vars'] : array();
    }

    /**
     * Fetch the host custom variables related to this object
     *
     * @return  $this
     */
    public function fetchHostVariables()
    {
        $query = $this->backend->select()->from('customvar', array(
            'varname',
            'varvalue',
            'is_json'
        ))
            ->where('object_type', static::TYPE_HOST)
            ->where('host_name', $this->host_name);

        $this->hostVariables = array();
        foreach ($query as $row) {
            if ($row->is_json) {
                $this->hostVariables[strtolower($row->varname)] = json_decode($row->varvalue);
            } else {
                $this->hostVariables[strtolower($row->varname)] = $row->varvalue;
            }
        }

        return $this;
    }

    /**
     * Fetch the service custom variables related to this object
     *
     * @return  $this
     *
     * @throws  ProgrammingError    In case this object is not a service
     */
    public function fetchServiceVariables()
    {
        if ($this->type !== static::TYPE_SERVICE) {
            throw new ProgrammingError('Cannot fetch service custom variables for non-service objects');
        }

        $query = $this->backend->select()->from('customvar', array(
            'varname',
            'varvalue',
            'is_json'
        ))
            ->where('object_type', static::TYPE_SERVICE)
            ->where('host_name', $this->host_name)
            ->where('service_description', $this->service_description);

        $this->serviceVariables = array();
        foreach ($query as $row) {
            if ($row->is_json) {
                $this->serviceVariables[strtolower($row->varname)] = json_decode($row->varvalue);
            } else {
                $this->serviceVariables[strtolower($row->varname)] = $row->varvalue;
            }
        }

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
            'author_name'       => 'downtime_author_name',
            'comment'           => 'downtime_comment',
            'duration'          => 'downtime_duration',
            'end'               => 'downtime_end',
            'entry_time'        => 'downtime_entry_time',
            'id'                => 'downtime_internal_id',
            'is_fixed'          => 'downtime_is_fixed',
            'is_flexible'       => 'downtime_is_flexible',
            'is_in_effect'      => 'downtime_is_in_effect',
            'name'              => 'downtime_name',
            'objecttype'        => 'object_type',
            'scheduled_end'     => 'downtime_scheduled_end',
            'scheduled_start'   => 'downtime_scheduled_start',
            'start'             => 'downtime_start'
        ))
            ->where('object_type', $this->type)
            ->order('downtime_is_in_effect', 'DESC')
            ->order('downtime_scheduled_start', 'ASC');
        if ($this->type === self::TYPE_SERVICE) {
            $downtimes
                ->where('service_host_name', $this->host_name)
                ->where('service_description', $this->service_description);
        } else {
            $downtimes
                ->where('host_name', $this->host_name);
        }
        $this->downtimes = $downtimes->getQuery()->fetchAll();
        return $this;
    }

    /**
     * Fetch the object's event history
     *
     * @return $this
     */
    public function fetchEventhistory()
    {
        $eventHistory = $this->backend
            ->select()
            ->from(
                'eventhistory',
                array(
                    'object_type',
                    'host_name',
                    'host_display_name',
                    'service_description',
                    'service_display_name',
                    'timestamp',
                    'state',
                    'output',
                    'type'
                )
            )
            ->where('object_type', $this->type)
            ->where('host_name', $this->host_name);

        if ($this->type === self::TYPE_SERVICE) {
            $eventHistory->where('service_description', $this->service_description);
        }

        $this->eventhistory = $eventHistory;
        return $this;
    }

    /**
     * Fetch the object's host groups
     *
     * @return $this
     */
    public function fetchHostgroups()
    {
        $this->hostgroups = $this->backend->select()
            ->from('hostgroup', array('hostgroup_name', 'hostgroup_alias'))
            ->where('host_name', $this->host_name)
            ->applyFilter($this->getFilter())
            ->fetchPairs();
        return $this;
    }

    /**
     * Fetch the object's service groups
     *
     * @return $this
     */
    public function fetchServicegroups()
    {
        $query = $this->backend->select()
            ->from('servicegroup', array('servicegroup_name', 'servicegroup_alias'))
            ->where('host_name', $this->host_name);

        if ($this->type === self::TYPE_SERVICE) {
            $query->where('service_description', $this->service_description);
        }

        $this->servicegroups = $query->applyFilter($this->getFilter())->fetchPairs();
        return $this;
    }

    /**
     * Fetch stats
     *
     * @return $this
     */
    public function fetchStats()
    {
        $this->stats = $this->backend->select()->from('servicestatussummary', array(
            'services_total',
            'services_ok',
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
            ->applyFilter($this->getFilter())
            ->fetchRow();
        return $this;
    }

    /**
     * Get all action urls configured for this monitored object
     *
     * @return array    All note urls as a string
     */
    public function getActionUrls()
    {
        return $this->resolveAllStrings(
            MonitoredObject::parseAttributeUrls($this->action_url)
        );
    }

    /**
     * Get the type of the object
     *
     * @param   bool $translate
     *
     * @return  string
     */
    public function getType($translate = false)
    {
        if ($translate !== false) {
            switch ($this->type) {
                case self::TYPE_HOST:
                    $type = mt('montiroing', 'host');
                    break;
                case self::TYPE_SERVICE:
                    $type = mt('monitoring', 'service');
                    break;
                default:
                    throw new InvalidArgumentException('Invalid type ' . $this->type);
            }
        } else {
            $type = $this->type;
        }
        return $type;
    }

    /**
     * Parse the content of the action_url or notes_url attributes
     *
     * Find all occurences of http links, separated by whitespaces and quoted
     * by single or double-ticks.
     *
     * @link http://docs.icinga.com/latest/de/objectdefinitions.html
     *
     * @param   string  $urlString  A string containing one or more urls
     * @return  array                   Array of urls as strings
     */
    public static function parseAttributeUrls($urlString)
    {
        if (empty($urlString)) {
            return array();
        }
        $links = array();
        if (strpos($urlString, "' ") === false) {
            $links[] = $urlString;
        } else {
            // parse notes-url format
            foreach (explode("' ", $urlString) as $url) {
                $url = strpos($url, "'") === 0 ? substr($url, 1) : $url;
                $url = strrpos($url, "'") === strlen($url) - 1 ? substr($url, 0, strlen($url) - 1) : $url;
                $links[] = $url;
            }
        }
        return $links;
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
            ->fetchContactgroups()
            ->fetchContacts()
            ->fetchCustomvars()
            ->fetchDowntimes();

        // Call fetchHostgroups or fetchServicegroups depending on the object's type
        $fetchGroups = 'fetch' . ucfirst($this->type) . 'groups';
        $this->$fetchGroups();

        return $this;
    }

    /**
     * Resolve macros in all given strings in the current object context
     *
     * @param   array   $strs   An array of urls as string
     *
     * @return  array
     */
    protected function resolveAllStrings(array $strs)
    {
        foreach ($strs as $i => $str) {
            $strs[$i] = Macro::resolveMacros($str, $this);
        }
        return $strs;
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

    public function __isset($name)
    {
        if (property_exists($this->properties, $name)) {
            return isset($this->properties->$name);
        } elseif (property_exists($this, $name)) {
            return isset($this->$name);
        }
        return false;
    }

    public function __get($name)
    {
        if (property_exists($this->properties, $name)) {
            return $this->properties->$name;
        } elseif (property_exists($this, $name)) {
            if ($this->$name === null) {
                $fetchMethod = 'fetch' . ucfirst($name);
                $this->$fetchMethod();
            }

            return $this->$name;
        } elseif (preg_match('/^_(host|service)_(.+)/i', $name, $matches)) {
            if (strtolower($matches[1]) === static::TYPE_HOST) {
                if ($this->hostVariables === null) {
                    $this->fetchHostVariables();
                }

                $customvars = $this->hostVariables;
            } else {
                if ($this->serviceVariables === null) {
                    $this->fetchServiceVariables();
                }

                $customvars = $this->serviceVariables;
            }

            $variableName = strtolower($matches[2]);
            if (isset($customvars[$variableName])) {
                return $customvars[$variableName];
            }

            return null; // Unknown custom variables MUST NOT throw an error
        } elseif (in_array($name, array('contact_name', 'contactgroup_name', 'hostgroup_name', 'servicegroup_name'))) {
            if ($name === 'contact_name') {
                if ($this->contacts === null) {
                    $this->fetchContacts();
                }

                return array_map(function ($el) { return $el->contact_name; }, $this->contacts);
            } elseif ($name === 'contactgroup_name') {
                if ($this->contactgroups === null) {
                    $this->fetchContactgroups();
                }

                return array_map(function ($el) { return $el->contactgroup_name; }, $this->contactgroups);
            } elseif ($name === 'hostgroup_name') {
                if ($this->hostgroups === null) {
                    $this->fetchHostgroups();
                }

                return array_keys($this->hostgroups);
            } else { // $name === 'servicegroup_name'
                if ($this->servicegroups === null) {
                    $this->fetchServicegroups();
                }

                return array_keys($this->servicegroups);
            }
        } elseif (strpos($name, $this->prefix) !== 0) {
            $propertyName = strtolower($name);
            $prefixedName = $this->prefix . $propertyName;
            if (property_exists($this->properties, $prefixedName)) {
                return $this->properties->$prefixedName;
            }

            if ($this->type === static::TYPE_HOST) {
                if ($this->hostVariables === null) {
                    $this->fetchHostVariables();
                }

                $customvars = $this->hostVariables;
            } else { // $this->type === static::TYPE_SERVICE
                if ($this->serviceVariables === null) {
                    $this->fetchServiceVariables();
                }

                $customvars = $this->serviceVariables;
            }

            if (isset($customvars[$propertyName])) {
                return $customvars[$propertyName];
            }
        }

        throw new InvalidPropertyException('Can\'t access property \'%s\'. Property does not exist.', $name);
    }

    /**
     * @deprecated
     */
    public static function fromParams(UrlParams $params)
    {
        if ($params->has('service') && $params->has('host')) {
            return new Service(MonitoringBackend::instance(), $params->get('host'), $params->get('service'));
        } elseif ($params->has('host')) {
            return new Host(MonitoringBackend::instance(), $params->get('host'));
        }
        return null;
    }
}
