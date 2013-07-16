<?php
namespace Test\Monitoring\Testlib\DataSource;

class ObjectFlags {
    public $flapping = 0;
    public $notifications = 1;
    public $active_checks = 1;
    public $passive_checks = 1;
    public $acknowledged = 0;
    public $in_downtime = 0;
    public $time = 0;

    public function __construct($ageInSeconds = null) {
        if(!is_int($ageInSeconds))
            $ageInSeconds = 0;
        $this->time = time()-$ageInSeconds;
    }

    public static function FLAPPING() {
        $flags = new ObjectFlags();
        $flags->flapping = 0;
        return $flags;
    }

    public static function DISABLE_NOTIFICATIONS() {
        $flags = new ObjectFlags();
        $flags->notifications = 0;
        return $flags;
    }

    public static function PASSIVE_ONLY() {
        $flags = new ObjectFlags();
        $flags->active_checks = 0;
        return $flags;
    }

    public static function ACTIVE_ONLY() {
        $flags = new ObjectFlags();
        $flags->passive_checks = 0;
        return $flags;
    }


    public static function DISABLED() {
        $flags = new ObjectFlags();
        $flags->passive_checks = 0;
        $flags->active_checks = 0;
        return $flags;
    }

    public static function ACKNOWLEDGED() {
        $flags = new ObjectFlags();
        $flags->acknowledged = 1;
        return $flags;
    }

    public static function IN_DOWNTIME() {
        $flags = new ObjectFlags();
        $flags->in_downtime = 1;
        return $flags;
    }

}

class TestFixtureObjectClosure
{
    private $scope;
    private $environment;

    public function __construct(TestFixture $environment, $scope)
    {
        $this->scope = $scope;
        $this->environment = $environment;
    }
    public function __call($string, $arguments)
    {
        $callArg = array($this->scope);
        $args = array_merge($callArg, $arguments);
        return call_user_func_array(array($this->environment, $string), $args);
    }
}

class TestFixture
{
    private $hosts = array();
    private $services = array();
    private $contacts = array();
    private $comments = array();
    private $servicegroups = array();
    private $hostgroups = array();
    
    public function getHosts()
    {
        return $this->hosts;
    }

    public function getServices()
    {
        return $this->services;
    }

    public function getContacts()
    {
        return $this->contacts;
    }

    public function getServicegroups()
    {
        return $this->servicegroups;
    }

    public function getHostgroups()
    {
        return $this->hostgroups;
    }

    public function getComments()
    {
        return $this->comments;
    }

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