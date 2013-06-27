<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication;

/**
*   Data holder object for authentication information
*
*   This object should be used instead of passing names and
*   passwords as primitives in order to allow additional information
*   to be provided (like the domain) when needed
**/
class Credentials
{
    protected $username;
    protected $password;
    protected $domain;
    
    /**
    *   Create a new credential object
    *   
    *   @param String   $username
    *   @param String   $password
    *   @param String   $domain
    **/
    public function __construct($username = "", $password = null, $domain = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->domain = $domain;
    }

    /**
    *   @return String
    **/
    public function getUsername()
    {
        return $this->username;
    }

    /**
    *   @param String $username
    **/
    public function setUsername($username)
    {
        return $this->username = $username;
    }
    
    /**
    *   @return String
    **/
    public function getPassword()
    {
        return $this->password;
    }

    /**
    *   @param String  $password
    **/
    public function setPassword($password)
    {
        return $this->password = $password;
    }

    /**
    *   @return String
    **/
    public function getDomain()
    {
        return $this->domain;
    }

    /**
    *   @param String  $domain
    **/
    public function setDomain($domain)
    {
        return $this->domain = $domain;
    }
}
