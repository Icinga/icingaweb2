<?php


namespace Icinga\Authentication;

use Icinga\Application\Logger as Logger;

class Manager
{
    const BACKEND_TYPE_USER = "User";
    const BACKEND_TYPE_GROUP = "Group";

    private static $instance = null;

    private $user = null;
    private $groups = array();
    private $userBackend = null;
    private $groupBackend = null;
    private $session = null;
    
    private function __construct($config = null, array $options = array())
    {
        if ($config === null) {
            $config = Config::getInstance()->authentication;
        }

        if (isset($options["userBackendClass"])) {
            $this->userBackend = $options["userBackendClass"];
        } elseif ($config->users !== null) {
            $this->userBackend = initBackend(BACKEND_TYPE_USER, $config->users);
        }

        if (isset($options["groupBackendClass"])) {
            $this->groupBackend = $options["groupBackendClass"];
        } elseif ($config->groups != null) {
            $this->groupBackend = initBackend(BACKEND_TYPE_GROUP, $config->groups);
        }

        if (!isset($options["sessionClass"])) {
            $this->session = new PhpSession($config->session);
        } else {
            $this->session = $options["sessionClass"];
        }
        if (isset($options["writeSession"]) && $options["writeSession"] === true) {
            $this->session->read(true);
        } else {
            $this->session->read();
        }
    }

    public static function getInstance($config = null, array $options = array())
    {
        if (self::$instance === null) {
            self::$instance = new Manager($config, $options);
        }
        return self::$instance;
    }

    public static function clearInstance()
    {
        self::$instance = null;
    }

    private function initBackend($authenticationTarget, $authenticationSource)
    {
        $userbackend = ucwords(strtolower($authenticationSource->backend));
        $class = '\\Icinga\\Authentication\\Backend\\' . $backend . $authenticationTarget. 'Backend';
        return new $class($authenticationSource);
    }

    public function authenticate(Credentials $credentials, $persist = true)
    {
        if (!$this->userBackend->hasUsername($credentials)) {
            Logger::info("Unknown user %s tried to log in", $credentials->getUsername());
            return false;
        }
        $this->user = $this->userBackend->authenticate($credentials);
        if ($this->user == null) {
            Logger::info("Invalid credentials for user %s provided", $credentials->getUsername());
            return false;
        }
        
        if ($persist == true) {
            $this->persistCurrentUser();
            $this->session->write();
        }
        return true;
    }

    public function persistCurrentUser()
    {
        $this->session->set("user", $this->user);
    }

    public function authenticateFromSession()
    {
        $this->user = $this->session->get("user", null);
    }

    public function isAuthenticated($ignoreSession = false)
    {
        if ($this->user === null && !$ignoreSession) {
            $this->authenticateFromSession();
        }
        return is_object($this->user);
    }

    public function removeAuthorization()
    {
        $this->user = null;
        $this->session->delete();
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getGroups()
    {
        return $this->user->getGroups();
    }
}
