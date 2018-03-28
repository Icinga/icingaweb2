<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga;

use DateTimeZone;
use InvalidArgumentException;
use Icinga\Application\Config;
use Icinga\Authentication\Role;
use Icinga\Exception\ProgrammingError;
use Icinga\User\Preferences;
use Icinga\Web\Navigation\Navigation;

/**
 *  This class represents an authorized user
 *
 *  You can retrieve authorization information (@TODO: Not implemented yet) or user information
 */
class User
{
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
     * {@link username} without {@link domain}
     *
     * @var string
     */
    protected $localUsername;

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
     * Information if the user is externally authenticated
     *
     * Keys:
     *
     * 0: origin username
     * 1: origin field name
     *
     * @var array
     */
    protected $externalUserInformation = array();

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
     * Roles of this user
     *
     * @var Role[]
     */
    protected $roles = array();

    /**
     * Preferences object
     *
     * @var Preferences
     */
    protected $preferences;

    /**
     * Whether the user is authenticated using a HTTP authentication mechanism
     *
     * @var bool
     */
    protected $isHttpUser = false;

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
     *
     * @return  $this
     */
    public function setPreferences(Preferences $preferences)
    {
        $this->preferences = $preferences;
        return $this;
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
     *
     * @return  $this
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
        return $this;
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
     * Set the user's restrictions
     *
     * @param   string[]    $restrictions
     *
     * @return  $this
     */
    public function setRestrictions(array $restrictions)
    {
        $this->restrictions = $restrictions;
        return $this;
    }

    /**
     * Get the roles of the user
     *
     * @return Role[]
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set the roles of the user
     *
     * @param   Role[]  $roles
     *
     * @return  $this
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Getter for username
     *
     * @return  string
     */
    public function getUsername()
    {
        return $this->domain === null ? $this->localUsername : $this->localUsername . '@' . $this->domain;
    }

    /**
     * Setter for username
     *
     * @param   string      $name
     *
     * @return  $this
     */
    public function setUsername($name)
    {
        $parts = explode('\\', $name, 2);
        if (count($parts) === 2) {
            list($this->domain, $this->localUsername) = $parts;
        } else {
            $parts = explode('@', $name, 2);
            if (count($parts) === 2) {
                list($this->localUsername, $this->domain) = $parts;
            } else {
                $this->localUsername = $name;
                $this->domain = null;
            }
        }

        return $this;
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
     *
     * @return  $this
     */
    public function setFirstname($name)
    {
        $this->firstname = $name;
        return $this;
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
     *
     * @return  $this
     */
    public function setLastname($name)
    {
        $this->lastname = $name;
        return $this;
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
     * @return  $this
     *
     * @throws  InvalidArgumentException    When an invalid mail is provided
     */
    public function setEmail($mail)
    {
        if ($mail !== null && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                sprintf('Invalid mail given for user %s: %s', $this->getUsername(), $mail)
            );
        }

        $this->email = $mail;
        return $this;
    }

    /**
     * Set the domain
     *
     * @param   string      $domain
     *
     * @return  $this
     */
    public function setDomain($domain)
    {
        $domain = trim($domain);

        if (strlen($domain)) {
            $this->domain = $domain;
        }

        return $this;
    }

    /**
     * Get whether the user has a domain
     *
     * @return  bool
     */
    public function hasDomain()
    {
        return $this->domain !== null;
    }

    /**
     * Get the domain
     *
     * @return  string
     *
     * @throws  ProgrammingError    If the user does not have a domain
     */
    public function getDomain()
    {
        if ($this->domain === null) {
            throw new ProgrammingError(
                'User does not have a domain.'
                . ' Use User::hasDomain() to check whether the user has a domain beforehand.'
            );
        }
        return $this->domain;
    }

    /**
     * Get whether the user's domain matches the given one
     *
     * @param   string|null $domain
     *
     * @return  bool
     */
    public function isInDomain($domain)
    {
        return $domain === null
            ? $this->domain === null
            : $this->domain !== null && ($domain === '*' || strtolower($this->domain) === strtolower($domain));
    }

    /**
     * Get the local username, ie. the username without its domain
     *
     * @return string
     */
    public function getLocalUsername()
    {
        return $this->localUsername;
    }

    /**
     * Set additional information about user
     *
     * @param   string      $key
     * @param   string      $value
     *
     * @return  $this
     */
    public function setAdditional($key, $value)
    {
        $this->additionalInformation[$key] = $value;
        return $this;
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
     * Set additional external user information
     *
     * @param string    $username
     * @param string    $field
     *
     * @return  $this
     */
    public function setExternalUserInformation($username, $field)
    {
        $this->externalUserInformation = array($username, $field);
        return $this;
    }

    /**
     * Get additional external user information
     *
     * @return array
     */
    public function getExternalUserInformation()
    {
        return $this->externalUserInformation;
    }

    /**
     * Return true if user has external user information set
     *
     * @return bool
     */
    public function isExternalUser()
    {
        return ! empty($this->externalUserInformation);
    }

    /**
     * Get whether the user is authenticated using a HTTP authentication mechanism
     *
     * @return bool
     */
    public function getIsHttpUser()
    {
        return $this->isHttpUser;
    }

    /**
     * Set whether the user is authenticated using a HTTP authentication mechanism
     *
     * @param   bool $isHttpUser
     *
     * @return  $this
     */
    public function setIsHttpUser($isHttpUser = true)
    {
        $this->isHttpUser = (bool) $isHttpUser;
        return $this;
    }

    /**
     * Whether the user has a given permission
     *
     * @param   string $requiredPermission
     *
     * @return  bool
     */
    public function can($requiredPermission)
    {
        if (isset($this->permissions['*']) || isset($this->permissions[$requiredPermission])) {
            return true;
        }

        $requiredWildcard = strpos($requiredPermission, '*');
        foreach ($this->permissions as $grantedPermission) {
            if ($requiredWildcard !== false) {
                if (($grantedWildcard = strpos($grantedPermission, '*')) !== false) {
                    $wildcard = min($requiredWildcard, $grantedWildcard);
                } else {
                    $wildcard = $requiredWildcard;
                }
            } else {
                $wildcard = strpos($grantedPermission, '*');
            }

            if ($wildcard !== false) {
                if (substr($requiredPermission, 0, $wildcard) === substr($grantedPermission, 0, $wildcard)) {
                    return true;
                }
            } elseif ($requiredPermission === $grantedPermission) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load and return this user's configured navigation of the given type
     *
     * @param   string  $type
     *
     * @return  Navigation
     */
    public function getNavigation($type)
    {
        $config = Config::navigation($type === 'dashboard-pane' ? 'dashlet' : $type, $this->getUsername());

        if ($type === 'dashboard-pane') {
            $panes = array();
            foreach ($config as $dashletName => $dashletConfig) {
                // TODO: Throw ConfigurationError if pane or url is missing
                $panes[$dashletConfig->pane][$dashletName] = $dashletConfig->url;
            }

            $navigation = new Navigation();
            foreach ($panes as $paneName => $dashlets) {
                $navigation->addItem(
                    $paneName,
                    array(
                        'type'      => 'dashboard-pane',
                        'dashlets'  => $dashlets
                    )
                );
            }
        } else {
            $navigation = Navigation::fromConfig($config);
        }

        return $navigation;
    }
}
