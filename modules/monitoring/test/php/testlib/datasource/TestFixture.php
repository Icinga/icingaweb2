<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Testlib\DataSource;

/**
 * Status flags for objects
 *
 */
class ObjectFlags {
    /**
     * 1 if the test host is flapping, otherwise 0
     * @var int
     */
    public $flapping = 0;

    /**
     * 1 if the test host has notifications enabled, otherwise 0
     * @var int
     */
    public $notifications = 1;

    /**
     * 1 if the test host is active, otherwise 0
     * @var int
     */
    public $active_checks = 1;

    /**
     * 1 if the test host allows passive checks, otherwise 0
     * @var int
     */
    public $passive_checks = 1;

    /**
     * 1 if the test host is acknowledged, otherwise 0
     * @var int
     */
    public $acknowledged = 0;

    /**
     * 1 if the test host is in a downtime, otherwise 0
     * @var int
     */
    public $in_downtime = 0;

    /**
     * 1 if the test host is pending, otherwise 0
     * @var int
     */
    public $is_pending = 0;

    /**
     * The last check and state change time as a UNIX timestamp
     * @var int
     */
    public $time = 0;

    /**
     * Create a new ObjectFlags instance with default values
     *
     * @param int $ageInSeconds     How old this check should be in seconds
     */
    public function __construct($ageInSeconds = null)
    {
        if(!is_int($ageInSeconds))
            $ageInSeconds = 0;
        $this->time = time()-$ageInSeconds;
    }

    /**
     * Create a new ObjectFlags object that is in 'flapping' state
     *
     * @return ObjectFlags
     */
    public static function FLAPPING()
    {
        $flags = new ObjectFlags();
        $flags->flapping = 0;
        return $flags;
    }

    /**
     * Create a new ObjectFlags object that is in 'pending' state
     *
     * @return ObjectFlags
     */
    public static function PENDING()
    {
        $flags = new ObjectFlags();
        $flags->is_pending = 1;
        return $flags;
    }

    /**
     * Create a new ObjectFlags object that is in 'notifications_disabled' state
     *
     * @return ObjectFlags
     */
    public static function DISABLE_NOTIFICATIONS()
    {
        $flags = new ObjectFlags();
        $flags->notifications = 0;
        return $flags;
    }

    /**
     * Create a new ObjectFlags object that has active checks disabled but passive enabled
     *
     * @return ObjectFlags
     */
    public static function PASSIVE_ONLY()
    {
        $flags = new ObjectFlags();
        $flags->active_checks = 0;
        return $flags;
    }

    /**
     * Create a new ObjectFlags object that has passive checks disabled but active enabled
     *
     * @return ObjectFlags
     */
    public static function ACTIVE_ONLY()
    {
        $flags = new ObjectFlags();
        $flags->passive_checks = 0;
        return $flags;
    }

    /**
     * Create a new ObjectFlags object that is neither active nor passive
     *
     * @return ObjectFlags
     */
    public static function DISABLED() {
        $flags = new ObjectFlags();
        $flags->passive_checks = 0;
        $flags->active_checks = 0;
        return $flags;
    }

    /**
     * Create a new ObjectFlags object that is in 'acknowledged' state
     *
     * @return ObjectFlags
     */
    public static function ACKNOWLEDGED()
    {
        $flags = new ObjectFlags();
        $flags->acknowledged = 1;
        return $flags;
    }

    /**
     * Create a new ObjectFlags object that is in a downtime
     *
     * @return ObjectFlags
     */
    public static function IN_DOWNTIME()
    {
        $flags = new ObjectFlags();
        $flags->in_downtime = 1;
        return $flags;
    }
}

/**
 * Internal class that adds an object scope on Fixture operations
 *
 * This class allows to use $fixture->addHost('host',0)->addService() instead('svc')
 * of $fixture->addHost('host',0); $fixture->addService('host', 'svc') as it encapsulates
 * the scope of the last called object and automatically adds it as the first parameter
 * of the next call.
 *
 */
class TestFixtureObjectClosure
{
    /**
     * The object (hostname or hostname/servicename pair) this scope represents
     * @var String|array
     */
    private $scope;

    /**
     * The Testfixture to operate on
     * @var TestFixture
     */
    private $environment;

    /**
     * Create a new scope using the TestFixture with the given host / service
     * as the scope
     *
     * @param TestFixture $environment  The testfixture to use for subsequent calls
     * @param $scope                    The scope to prepend to all further calls
     */
    public function __construct(TestFixture $environment, $scope)
    {
        $this->scope = $scope;
        $this->environment = $environment;
    }

    /**
     * Magic method that forwards all function calls to the environment
     * but prepends the scope.
     *
     * A call func($arg1) to this class would be rewritten to $environment->func($scope, $arg1)
     *
     * @param string $string        The method that should be called with this scope
     * @param array $arguments      The arguments the user passed to the function
     * @return mixed                The result of the function call
     */
    public function __call($string, $arguments)
    {
        $callArg = array($this->scope);
        $args = array_merge($callArg, $arguments);
        return call_user_func_array(array($this->environment, $string), $args);
    }
}

/**
 *  Create test-states that can be persisted to different backends
 *  using DataSourceTestSetup.
 *
 *  This class provides not all fields used in monitoring, but the most
 *  important ones (and the ones that are missing should be added during
 *  developmen).
 *
 *  Usage:
 *  <code>
 *  $fixture = new TestFixture();
 *  // adding a new critical, but acknowledged host
 *  $fixture->addHost("hostname", 1, ObjectFlags::ACKNOWLEDGED())
 *
 *      // add a comment to the host (this has to be done before adding services)
 *      ->addComment("author", "comment text")
 *
 *      // assign to hostgroup
 *      ->addToHostgroup("myHosts")
 *
 *      // and add three services to this host
 *      ->addService("svc1", 0) // Service is ok
 *      ->addService("svc2", 1, ObjectFlags::PASSIVE) // service is warning and passive
 *      ->addService("svc3", 2, null, array("notes_url" => "test.html")) // critical with notes url
 *          ->addComment("author", "what a nice service comment") // add a comment to the service
 *          ->addToServicegroup("alwaysdown"); // add svc3 to servicegroup
 *
 *  // Create the datasource from this fixture, here form MySQL
 *  $ds = new DataSourceTestSetup("mysql");
 *  $ds->setup();
 *  // insert fixture
 *  $ds->insert($fixture);
 *  </code>
 *
 */
class TestFixture
{
    /**
     * Internal dataholder for all defined hosts
     * @var array
     */
    private $hosts = array();

    /**
     * Internal holder for all defined services
     * @var array
     */
    private $services = array();

    /**
     * Internal holder for all defined contacts
     * @var array
     */
    private $contacts = array();

    /**
     * Internal holder for all defined comments
     * @var array
     */
    private $comments = array();

    /**
     * Internal holder for all defined servicegroups
     * @var array
     */
    private $servicegroups = array();

    /**
     * Internal holder for all defined hostgroups
     * @var array
     */
    private $hostgroups = array();

    /**
     * Return array with all defined hostobjects
     *
     * @return array    Returns an array of host-arrays, which have the following fields
     *                      - 'name'            : The name of the host
     *                      - 'state'           : The state of the host (0,1,2)
     *                      - 'address'         : The string representation of the address (127.0.0.1 as default)
     *                      - 'flags'           : An ObjectFlags object containing additional state information
     *                      - 'icon_image'      : The icon image of this host (default: 'icon.png')
     *                      - 'notes_url'       : The notes url of this host (default empty string)
     *                      - 'action_url'      : The action url of this host (default empty string)
     *                      - 'contacts'        : An array of contact objects (having 'name' as the most important field)
     *                      - 'customvariables' : An associative "cv_name"=>"cv_value" array containing the customvariables
     */
    public function getHosts()
    {
        return $this->hosts;
    }

    /**
     * Return array with all defined service objects
     *
     * @return array    Returns an array of service-arrays, which have the following fields
     *                      - 'name'            : The name of the service
     *                      - 'host'            : A reference to the hostobject
     *                      - 'state'           : The state of the service (0,1,2,3)
     *                      - 'flags'           : An ObjectFlags object containing additional state information
     *                      - 'icon_image'      : The icon image of this service (default: 'icon.png')
     *                      - 'notes_url'       : The notes url of this service (default empty string)
     *                      - 'action_url'      : The action url of this service (default empty string)
     *                      - 'contacts'        : An array of contact objects (having 'name' as the most important field)
     *                      - 'customvariables' : An associative "cv_name"=>"cv_value" array containing the customvariables
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Return array with all defined contacts
     *
     * @return array    Returns an array of contact-arrays, which have the following fields
     *                  - 'alias'   : The name of the contact
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Return array with all defined servicegroups
     *
     * @return array    Returns an array of group-arrays in the following format:
     *                  - 'name'    :   The name of the group
     *                  - 'members' :   An array of service objects that belong to this group
     */
    public function getServicegroups()
    {
        return $this->servicegroups;
    }

    /**
     * Return an array with all defined hostgroups
     *
     * @return array    Returns an array of group-arrays in the following format:
     *                  - 'name'    :   The name of the group
     *                  - 'members' :   An array of host objects that belong to this group
     */
    public function getHostgroups()
    {
        return $this->hostgroups;
    }

    /**
     * Return an array of service and hostcomments
     *
     * @return array    Returns an array of comment arrays in the following format:
     *                  - 'service' (if servicecomment) : A reference to the service object this comment belongs to
     *                  - 'host'    (if hostcomment)    : A reference to the host object this comment belongs to
     *                  - 'author'                      : The author of this comment
     *                  - 'text'                        : The comment text
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Add a new host to this TestFixture
     *
     * @param string $name              The name of the host to add
     * @param int $state                The state of the host to add (0,1,2)
     * @param ObjectFlags $flags        (optional)  An @see ObjectFlags object defining additional state inforamtion
     * @param array $additional         (optional)  An array with additional object fields
     *
     * @return TestFixtureObjectClosure The TestFixture with the newly added host as the scope
     */
    public function addHost($name, $state, ObjectFlags $flags = null, array $additional = array()) {
        if ($flags === null) {
            $flags = new ObjectFlags();
        }
        if (isset($this->hosts[$name])) {
            throw new Exception('Tried to create hosts twice');
        }
        $this->hosts[$name] = array(
            'name'              => $name,
            'state'             => $state,
            'address'           => '127.0.0.1',
            'flags'             => $flags,
            'icon_image'        => 'icon.png',
            'notes_url'         => '',
            'action_url'        => '',
            'contacts'          => array(),
            'customvariables'   => array()

        );
        $this->hosts[$name] = array_merge($this->hosts[$name], $additional);
        return new TestFixtureObjectClosure($this, $name);
    }

    /**
     * Add a new service to this TestFixture
     *
     * @param string $host              The name of the host this service belongs to (must exist prior to service creation)
     * @param string $name              The name of the service to add
     * @param int $state                The state of the service to add (0,1,2,3)
     * @param ObjectFlags $flags        (optional)  An @see ObjectFlags object defining additional state information
     * @param array $additional         (optional)  An array with additional object fields
     *
     * @return TestFixtureObjectClosure The TestFixture with the newly added service as the scope
     */
    public function addService($host, $name, $state, ObjectFlags $flags = null, array $additional = array()) {
        // when called in service scope only use the host
        if (is_array($host)) {
            $host = $host[0];
        }
        if ($flags === null) {
            $flags = new ObjectFlags();
        }
        if (!isset($this->hosts[$host])) {
            throw new Exception('Tried to create service for non existing host '.$host);
        }
        if (isset($this->services[$name])) {
            throw new Exception('Tried to create service twice '.$name);
        }
        $this->services[$host.';'.$name] = array(
            'host'              =>  &$this->hosts[$host],
            'name'              =>  $name,
            'state'             =>  $state,
            'contacts'          =>  array(),
            'icon_image'        => 'icon.png',
            'notes_url'         => '',
            'action_url'        => '',
            'customvariables'   => array(),
            'flags'             =>  $flags
        );
        $this->services[$host.';'.$name] = array_merge($this->services[$host.';'.$name], $additional);

        return new TestFixtureObjectClosure($this, array($host, $name));
    }

    /**
     * Add a new comment to the host or service provided in $hostOrServiceHostPair
     *
     * @param string|array $hostOrServiceHostPair   Either a string with the hostname or an array with the hostname
     *                                              as the first and the servicename as the second element
     * @param $author                               The author for the coment
     * @param $text                                 The content of the comment
     * @return TestFixtureObjectClosure             The TestFixture with the comment owner as the scope
     */
    public function addComment($hostOrServiceHostPair, $author, $text) {
        if (is_array($hostOrServiceHostPair)) {
            if (!isset($this->services[$hostOrServiceHostPair[0].';'.$hostOrServiceHostPair[1]])) {
                throw new Exception('Tried to add a comment for a nonexisting service '.$hostOrServiceHostPair[1]);
            }
            $this->comments[] = array(
                'service'   => &$this->services[$hostOrServiceHostPair[0].';'.$hostOrServiceHostPair[1]],
                'author'    => $author,
                'text'      => $text
            );
        } else {
            if (!isset($this->hosts[$hostOrServiceHostPair])) {
                throw new Exception('Tried to add a comment for a nonexisting host '.$hostOrServiceHostPair);
            }
            $this->comments[] = array(
                'host'      => &$this->hosts[$hostOrServiceHostPair],
                'author'    => $author,
                'text'      => $text
            );
        }
        return new TestFixtureObjectClosure($this, $hostOrServiceHostPair);
    }

    /**
     * Assign a new contact to a host or service
     *
     * @param $hostOrServiceHostPair        Either a string with the hostname or an array with the hostname
     *                                      as the first and the servicename as the second element
     * @param $contactname                  The contactname to assign (will be created)
     * @return TestFixtureObjectClosure     The TestFixture with the host or service as the scope
     */
    public function assignContact($hostOrServiceHostPair, $contactname) {
        $this->contacts[$contactname] = array('alias' => $contactname);
        if (is_array($hostOrServiceHostPair)) {
            if (!isset($this->services[$hostOrServiceHostPair[0].';'.$hostOrServiceHostPair[1]])) {
                throw new Exception('Tried to add a comment for a nonexisting service '.$hostOrServiceHostPair[1]);
            }
            $service = $this->services[$hostOrServiceHostPair[0].';'.$hostOrServiceHostPair[1]];
            $service['contacts'][] = &$this->contacts[$contactname];
        } else {
            if (!isset($this->hosts[$hostOrServiceHostPair])) {
                throw new Exception('Tried to add a comment for a nonexisting host '.$hostOrServiceHostPair);
            }
            $host = $this->hosts[$hostOrServiceHostPair];
            $host['contacts'][] = &$this->contacts[$contactname];
        }
        return new TestFixtureObjectClosure($this, $hostOrServiceHostPair);
    }

    /**
     * Add a host to a hostgroup
     *
     * Create the new hostgroup if it not exists yet, otherwise just add the
     * host to it
     *
     * @param string $host                   The name of the host to add to the hostgroup
     * @param string $groupname             The name of the hostgroup
     * @return TestFixtureObjectClosure     The TestFixture with the host as the scope
     */
    public function addToHostgroup($host, $groupname) {
        // check if in service scope
        if (is_array($host)) {
            $host = $host[0];
        }
        if (!isset($this->hosts[$host])) {
            throw new Exception('Tried to add non-existing host '.$host.' to hostgroup ');
        }
        if (!isset($this->hostgroups[$groupname])) {
            $this->hostgroups[$groupname] = array("name" => $groupname, "members" => array());
        }
        $this->hostgroups[$groupname]["members"][] = &$this->hosts[$host];
        return new TestFixtureObjectClosure($this, $host);
    }

    /**
     * Add service to a servicegroup
     *
     * Create the new service if it not exists yet, otherwise just add the service
     *
     * @param array $serviceHostPair        An array containing the hostname as the first and the
     *                                      servicename as the second element
     * @param string $groupname             The name of the servicegroup
     * @return TestFixtureObjectClosure     The TestFixture with the service as the scope
     */
    public function addToServicegroup(array $serviceHostPair, $groupname) {
        if (!isset($this->services[$serviceHostPair[0].";".$serviceHostPair[1]])) {
            throw new Exception('Tried to add non-existing service '.$serviceHostPair[1].' to servicegroup ');
        }
        $service = &$this->services[$serviceHostPair[0].";".$serviceHostPair[1]];
        if (!isset($this->servicegroups[$groupname])) {
            $this->servicegroups[$groupname] = array("name" => $groupname, "members" => array());
        }
        $this->servicegroups[$groupname]["members"][] = &$service;
        return new TestFixtureObjectClosure($this, $serviceHostPair);
    }
}
