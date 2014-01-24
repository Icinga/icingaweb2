<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga;

use \DateTimeZone;
use \Exception;
use \InvalidArgumentException;
use Icinga\User\Preferences;
use Icinga\User\Message;
use Icinga\Application\Config;

/**
 *  This class represents an authorized user
 *
 *  You can retrieve authorization information (@TODO: Not implemented yet) or
 *  to retrieve user information
 */
class User
{
    /**
     * Username
     *
     * @var string
     */
    private $username;

    /**
     * Firstname
     *
     * @var string
     */
    private $firstname;

    /**
     * Lastname
     *
     * @var string
     */
    private $lastname;

    /**
     * Users email address
     *
     * @var string
     */
    private $email;

    /**
     * Domain
     *
     * @var string
     */
    private $domain;

    /**
     * More information about user
     *
     * @var array
     */
    private $additionalInformation = array();

    /**
     * Set of permissions
     *
     * @var array
     */
    private $permissions = array();

    /**
     * Set of restrictions
     *
     * @var array
     */
    private $restrictions = array();

    /**
     * Groups for this user
     *
     * @var array
     */
    private $groups = array();

    /**
     * Preferences object
     *
     * @var Preferences
     */
    private $preferences;

    /**
     * Queued notifications for this user.
     *
     * @var array()
     */
    private $messages;

    /**
     * Creates a user object given the provided information
     *
     * @param string $username
     * @param string $firstname
     * @param string $lastname
     * @param string $email
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
     * @param Preferences $preferences
     */
    public function setPreferences(Preferences $preferences)
    {
        $this->preferences = $preferences;
    }

    /**
     * Getter for preferences
     *
     * @return Preferences
     */
    public function getPreferences()
    {
        return $this->preferences;
    }

    /**
     * Return all groups this user belongs to
     *
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Set the groups this user belongs to
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * Return true if the user is a member of this group
     *
     * @param  string $group
     * @return boolean
     */
    public function isMemberOf($group)
    {
        return in_array($group, $this->groups);
    }

    /**
     * Return permission information for this user
     *
     * @return Array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Return restriction information for this user
     *
     * @return Array
     */
    public function getRestrictions($name)
    {
        if (array_key_exists($name, $this->restrictions)) {
            return $this->restrictions[$name];
        }
        return array();
    }

    /**
     * Getter for username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Setter for username
     *
     * @param string $name
     */
    public function setUsername($name)
    {
        $this->username = $name;
    }

    /**
     * Getter for firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Setter for firstname
     *
     * @param string $name
     */
    public function setFirstname($name)
    {
        $this->firstname = $name;
    }

    /**
     * Getter for lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Setter for lastname
     *
     * @param string $name
     */
    public function setLastname($name)
    {
        $this->lastname = $name;
    }

    /**
     * Getter for email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Setter for mail
     *
     * @param  string $mail
     * @throws InvalidArgumentException When an invalid mail is provided
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
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Getter for domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Load permissions for this user from permissions.ini
     *
     * TODO: - Separate this from the user object once possible
     *       - Support group permissions once groups are available
     *
     * @return self
     */
    public function loadPermissions()
    {
        try {
          // TODO: Config::app should gracefully handle missing files
          $config = Config::app('permissions');
        } catch (Exception $e) {
          return $this;
        }
        foreach ($config as $section) {
            if ($section->get('user') !== $this->username) {
                continue;
            }
            foreach ($section->toArray() as $key => $val) {
                if (false !== ($pos = strpos($key, '_'))
                    && substr($key, 0, $pos) === 'permission')
                {
                    $this->permissions[] = $val;
                }
            }
        }
        return $this;
    }

    /**
     * Load restrictions for this user from restrictions.ini
     *
     * TODO: - Separate this from the user object once possible
     *       - Support group restrictions once groups are available
     *
     * @return self
     */
    public function loadRestrictions()
    {
        try {
            // TODO: Config::app should gracefully handle missing files
            $config = Config::app('restrictions');
        } catch (Exception $e) {
            return $this;
        }

        foreach ($config as $section) {
            if ($section->get('user') !== $this->username) {
                continue;
            }
            if (! array_key_exists($section->name, $this->restrictions)) {
                $this->restrictions[$section->name] = array();
            }
            $this->restrictions[$section->name][] = $section->restriction;
        }
        return $this;
    }

    /**
     * Set additional information about user
     *
     * @param string $key
     * @param string $value
     */
    public function setAdditional($key, $value)
    {
        $this->additionalInformation[$key] = $value;
    }

    /**
     * Getter for additional information
     *
     * @param  string $key
     * @return mixed|null
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
     * @param Message $msg  The message
     */
    public function addMessage(Message $msg)
    {
        $this->messages[] = $msg;
    }

    /**
     * Get all currently pending messages
     *
     * @return array    the messages
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
