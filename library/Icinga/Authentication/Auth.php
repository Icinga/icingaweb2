<?php

namespace Icinga\Authentication;

use Icinga\Exception;
use Zend_Session_Namespace as SessionNamespace;

class Auth
{
    protected static $instance;
    protected $userInfo;
    protected $session;

    final private function __construct()
    {
        $this->session = new SessionNamespace('IcingaAuth');
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Auth();
        }
        return self::$instance;
    }

    public function isAuthenticated()
    {
        if ($this->userInfo === null) {
            if ($sessionInfo = $this->session->userInfo) {
                $this->userInfo = $sessionInfo;
            }
        }
        return is_object($this->userInfo) && ! empty($this->userInfo->username);
    }

    public function getUsername()
    {
        $this->assertIsAuthenticated();
        return $this->userInfo->username;
    }

    public function getEmail()
    {
        $this->assertIsAuthenticated();
        return $this->userInfo->email;
    }

    public function setAuthenticatedUser(User $user)
    {
        $this->userInfo = (object) array(
            'username'    => $user->username,
            'permissions' => $user->getPermissionList(),
            'email'       => $user->email,
        );
        $this->session->userInfo = $this->userInfo;
    }

    public function forgetAuthentication()
    {
        unset($this->session->userInfo);
        $this->userInfo = null;
    }

    public function hasPermission($route, $flags = 0x01)
    {
        $this->assertBeingAuthenticated();
        if (! array_key_exists($route, $this->userInfo->permissions)) {
            return false;
        }

        return $this->userInfo->permissions[$route] & $flags === $flags;
    }

    protected function assertIsAuthenticated()
    {
        if (! $this->isAuthenticated()) {
            throw new Exception\ProgrammingError(
                'Cannot fetch properties of a non-authenticated user'
            );
        }
    }
}
