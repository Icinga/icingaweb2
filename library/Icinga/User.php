<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga;

use DateTimeZone;
use InvalidArgumentException;
use Icinga\User\Preferences;
use Icinga\User\Message;

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
     * Queued notifications for this user.
     *
     * @var array()
     */
    protected $messages;

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
     * Return permission information for this user
     *
     * @return  array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Setter for permissions
     *
     * @param   array   $permissions
     */
    public function setPermissions(array $permissions)
    {
        $this->permissions = $permissions;
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
     * Add a message that can be accessed from future requests, to this user.
     *
     * This function does NOT automatically write to the session, messages will not be persisted until you do.
     *
     * @param   Message     $msg    The message
     */
    public function addMessage(Message $msg)
    {
        $this->messages[] = $msg;
    }

    /**
     * Get all currently pending messages
     *
     * @return  array   The messages
     */
    public function getMessages()
    {
        return isset($this->messages) ? $this->messages : array();
    }

    /**
     * Remove all messages from this user
     *
     * This function does NOT automatically write the session, messages will not be persisted until you do.
     */
    public function clearMessages()
    {
        $this->messages = null;
    }
}
