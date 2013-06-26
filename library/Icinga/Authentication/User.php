<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Icinga Authentication User class
 *
 * @package Icinga\Authentication
 */
namespace Icinga\Authentication;

/**
 * This class represents a user object
 *
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Application
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
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

    public function getGroups()
    {
        return $this->groups;
    }

    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    public function isMemberOf(Group $group)
    {
        return in_array($group, $this->groups);
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($name)
    {
        $this->username = $name;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname($name)
    {
        $this->firstname = $name;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($name)
    {
        $this->lastname = $name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($mail)
    {
        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $this->mail = $mail;
        } else {
            throw new InvalidArgumentException("Invalid mail given for user $this->username: $mail");
        }
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setAdditional($key, $value)
    {
        $this->additionalInformation[$key] = $value;
    }

    public function getAdditional($key)
    {
        if (isset($this->additionalInformation[$key])) {
            return $this->additionalInformation[$key];
        }
        return null;
    }
}
