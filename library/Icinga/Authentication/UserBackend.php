<?php

namespace Icinga\Authentication;

class UserBackend
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->init();
    }

    protected function init()
    {
    }

    public function hasUsername($username)
    {
        return false;
    }

    public function authenticate($username, $password = null)
    {
        return false;
    }
}
