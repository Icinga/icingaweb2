<?php

namespace Icinga\Authentication;


class Credentials
{
    protected $username;
    protected $password;
    protected $domain;


    public function __construct($username = "", $password = null, $domain = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->domain = $domain;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        return $this->username = $username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        return $this->password = $password;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setDomain($domain)
    {
        return $this->domain = $domain;
    }
}
