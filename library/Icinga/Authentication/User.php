<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication;

/**
 *  This class represents an authorized user and can be used
 *  to retrieve authorization information (@TODO: Not implemented yet) or
 *  to retrieve user information 
 *
 */
class User
{
    public $username = "";
    public $firstname = "";
    public $lastname = "";
    public $email = "";
    public $domain = "";
    public $additionalInformation = array();

    public $permissions = array();
    public $groups = array();
    
    /**
    *   Creates a user object given the provided information
    *   
    *   @param String $username
    *   @param String $firstname
    *   @param String $lastname
    *   @param String $email
    **/
    public function __construct($username, $firstname = null, $lastname = null, $email = null)
    {
        $this->setUsername($username);

        if ($firstname !== null) {
            $this->setFirstname($firstname);
        }

        if ($lastname !== null) {
            $this->setLastname($lastname);
        }

        if ($email !== null) {
            $this->setEmail($email);
        }
    }

    /**
    *   Returns all groups this user belongs to
    *
    *   @return Array 
    **/
    public function getGroups()
    {
        return $this->groups;
    }

    /**
    *   Sets the groups this user belongs to
    *   
    *   @return Array
    **/
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }
    
    /**
    *   Returns true if the user is a member of this group
    *   
    *   @return Boolean 
    **/
    public function isMemberOf(Group $group)
    {
        return in_array($group, $this->groups);
    }

    /**
    *   Returns permission information for this user
    *
    *   @return Array
    **/
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
    *   @return String
    **/
    public function getUsername()
    {
        return $this->username;
    }

    /**
    *   @param String $name
    **/
    public function setUsername($name)
    {
        $this->username = $name;
    }

    /**
    *  @return String
    **/
    public function getFirstname()
    {
        return $this->firstname;
    }

    /*+
    *   @param String $name
    **/
    public function setFirstname($name)
    {
        $this->firstname = $name;
    }

    /**
    *  @return String
    **/
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
    *   @param String $name     
    **/
    public function setLastname($name)
    {
        $this->lastname = $name;
    }

    /**
    *  @return String
    **/
    public function getEmail()
    {
        return $this->email;
    }
    
    /**
    *   @param String $mail
    *   
    *   @throws \InvalidArgumentException   When an invalid mail is provided
    **/
    public function setEmail($mail)
    {
        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $this->mail = $mail;
        } else {
            throw new \InvalidArgumentException("Invalid mail given for user $this->username: $mail");
        }
    }
    
    /**
    *   @param String $domain
    **/
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
    *  @return String
    **/
    public function getDomain()
    {
        return $this->domain;
    }

    /**
    *   @param String $key
    *   @param String $value
    **/
    public function setAdditional($key, $value)
    {
        $this->additionalInformation[$key] = $value;
    }

    /**
    *  @return mixed
    **/
    public function getAdditional($key)
    {
        if (isset($this->additionalInformation[$key])) {
            return $this->additionalInformation[$key];
        }
        return null;
    }
}
