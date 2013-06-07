<?php

namespace Icinga\Authentication;

class Backend
{
    protected $userBackend;

    public function __construct($config)
    {
        $this->config = $config;
        $userbackend = ucwords(strtolower($config->users->backend));
        $class = '\\Icinga\\Authentication\\' . $userbackend . 'UserBackend';
        $this->userBackend = new $class($config->users);
    }

    public function hasUsername($username)
    {
        return $this->userBackend->hasUsername($username);
    }

    public function authenticate($username, $password = null)
    {
        return $this->userBackend->authenticate($username, $password);
    }
}
