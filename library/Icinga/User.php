<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga;

use DateTimeZone;
use InvalidArgumentException;
use Icinga\User\Preferences;

/**
 *  This class represents an authorized user
 *
 *  You can retrieve authorization information (@TODO: Not implemented yet) or user information
 */
class User
{
    /**
     * Username
     *
     * @var string
     */
    protected $username;

    /**
     * Firstname
     *
     * @var string
     */
    protected $firstname;

    /**
     * Lastname
     *
     * @var string
     */
    protected $lastname;

    /**
     * Users email address
     *
     * @var string
     */
    protected $email;

    /**
     * Domain
     *
     * @var string
     */
    protected $domain;

    /**
     * More information about this user
     *
     * @var array
     */
    protected $additionalInformation = array();

    /**
     * Information if the user is external authenticated
     *
     * Keys:
     *
     * 0: origin username
     * 1: origin field name
     *
     * @var array
     */
    protected $remoteUserInformation = array();

    /**
     * Set of permissions
     *
     * @var array
     */
    protected $permissions = array();

    /**
     * Set of restrictions
     *
     * @var array
     */
    protected $restrictions = array();

    /**
     * Groups for this user
     *
     * @var array
     */
    protected $groups = array();

    /**
     * Preferences object
     *
     * @var Preferences
     */
    protected $preferences;

    /**
     * Creates a user object given the provided information
     *
     * @param   string      $username
     * @param   string      $firstname
     * @param   string      $lastname
     * @param   string      $email
     */
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
     * Setter for preferences
     *
     * @param   Preferences     $preferences
     */
    public function setPreferences(Preferences $preferences)
    {
        $this->preferences = $preferences;
    }

    /**
     * Getter for preferences
     *
     * @return  Preferences
     */
    public function getPreferences()
    {
        if ($this->preferences === null) {
            $this->preferences = new Preferences();
        }

        return $this->preferences;
    }

    /**
     * Return all groups this user belongs to
     *
     * @return  array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Set the groups this user belongs to
     *
     * @param   array   $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * Return true if the user is a member of this group
     *
     * @param   string  $group
     *
     * @return  boolean
     */
    public function isMemberOf($group)
    {
        return in_array($group, $this->groups);
    }

    /**
     * Get the user's permissions
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Set the user's permissions
     *
     * @param   array $permissions
     *
     * @return  $this
     */
    public function setPermissions(array $permissions)
    {
        if (! empty($permissions)) {
            natcasesort($permissions);
            $this->permissions = array_combine($permissions, $permissions);
        }
        return $this;
    }

    /**
     * Return restriction information for this user
     *
     * @param   string    $name
     *
     * @return  array
     */
    public function getRestrictions($name)
    {
        if (array_key_exists($name, $this->restrictions)) {
            return $this->restrictions[$name];
        }

        return array();
    }

    /**
     * Settter for restrictions
     *
     * @param   array   $restrictions
     */
    public function setRestrictions(array $restrictions)
    {
        $this->restrictions = $restrictions;
    }

    /**
     * Getter for username
     *
     * @return  string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Setter for username
     *
     * @param   string      $name
     */
    public function setUsername($name)
    {
        $this->username = $name;
    }

    /**
     * Getter for firstname
     *
     * @return  string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Setter for firstname
     *
     * @param   string      $name
     */
    public function setFirstname($name)
    {
        $this->firstname = $name;
    }

    /**
     * Getter for lastname
     *
     * @return  string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Setter for lastname
     *
     * @param   string      $name
     */
    public function setLastname($name)
    {
        $this->lastname = $name;
    }

    /**
     * Getter for email
     *
     * @return  string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Setter for mail
     *
     * @param   string      $mail
     *
     * @throws  InvalidArgumentException    When an invalid mail is provided
     */
    public function setEmail($mail)
    {
        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $this->email = $mail;
        } else {
            throw new InvalidArgumentException("Invalid mail given for user $this->username: $mail");
        }
    }

    /**
     * Setter for domain
     *
     * @param   string      $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Getter for domain
     *
     * @return  string
     */
    public function getDomain()
    {
        return $this->domain;
    }


    /**
     * Set additional information about user
     *
     * @param   string      $key
     * @param   string      $value
     */
    public function setAdditional($key, $value)
    {
        $this->additionalInformation[$key] = $value;
    }

    /**
     * Getter for additional information
     *
     * @param   string      $key
     * @return  mixed|null
     */
    public function getAdditional($key)
    {
        if (isset($this->additionalInformation[$key])) {
            return $this->additionalInformation[$key];
        }

        return null;
    }

    /**
     * Retrieve the user's timezone
     *
     * If the user did not set a timezone, the default timezone set via config.ini is returned
     *
     * @return  DateTimeZone
     */
    public function getTimeZone()
    {
        $tz = $this->preferences->get('timezone');
        if ($tz === null) {
            $tz = date_default_timezone_get();
        }

        return new DateTimeZone($tz);
    }

    /**
     * Set additional remote user information
     *
     * @param stirng    $username
     * @param string    $field
     */
    public function setRemoteUserInformation($username, $field)
    {
        $this->remoteUserInformation = array($username, $field);
    }

    /**
     * Get additional remote user information
     *
     * @return array
     */
    public function getRemoteUserInformation()
    {
        return $this->remoteUserInformation;
    }

    /**
     * Return true if user has remote user information set
     *
     * @return bool
     */
    public function isRemoteUser()
    {
        return ! empty($this->remoteUserInformation);
    }

    /**
     * Whether the user has a given permission
     *
     * @param   string $permission
     *
     * @return  bool
     */
    public function can($permission)
    {
        if (isset($this->permissions['*']) || isset($this->permissions[$permission])) {
            return true;
        }
        // If the permission to check contains a wildcard, grant the permission if any permit related to the permission
        // matches
        $any = strpos($permission, '*');
        foreach ($this->permissions as $permitted) {
            if ($any !== false) {
                $wildcard = $any;
            } else {
                // If the permit contains a wildcard, grant the permission if it's related to the permit
                $wildcard = strpos($permitted, '*');
            }
            if ($wildcard !== false) {
                if (substr($permission, 0, $wildcard) === substr($permitted, 0, $wildcard)) {
                    return true;
                }
            } elseif ($permission === $permitted) {
                return true;
            }
        }
        return false;
    }
}
