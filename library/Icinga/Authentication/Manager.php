<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication;

use Exception;
use Zend_Config;
use Icinga\User;
use Icinga\Web\Session;
use Icinga\Logger\Logger;
use Icinga\Exception\NotReadableError;
use Icinga\Application\Config as IcingaConfig;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;

class Manager
{
    /**
     * Singleton instance
     *
     * @var self
     */
    private static $instance;

    /**
     * Authenticated user
     *
     * @var User
     **/
    private $user;

    /**
     * If the user was authenticated from the REMOTE_USER server variable
     *
     * @var Boolean
     */
    private $fromRemoteUser = false;

    private function __construct()
    {
    }

    /**
     * Get the authentication manager
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function setAuthenticated(User $user, $persist = true)
    {
        $username = $user->getUsername();
        try {
            $config = IcingaConfig::app();
        } catch (NotReadableError $e) {
            Logger::error(
                new Exception('Cannot load preferences for user "' . $username . '". An exception was thrown', 0, $e)
            );
            $config = new Zend_Config(array());
        }
        if (($preferencesConfig = $config->preferences) !== null) {
            try {
                $preferencesStore = PreferencesStore::create(
                    $preferencesConfig,
                    $user
                );
                $preferences = new Preferences($preferencesStore->load());
            } catch (NotReadableError $e) {
                Logger::error(
                    new Exception(
                        'Cannot load preferences for user "' . $username . '". An exception was thrown', 0, $e
                    )
                );
                $preferences = new Preferences();
            }
        } else {
            $preferences = new Preferences();
        }
        $user->setPreferences($preferences);
        $membership = new Membership();
        $groups = $membership->getGroupsByUsername($username);
        $user->setGroups($groups);
        $admissionLoader = new AdmissionLoader();
        $user->setPermissions(
            $admissionLoader->getPermissions($username, $groups)
        );
        $user->setRestrictions(
            $admissionLoader->getRestrictions($username, $groups)
        );
        $this->user = $user;
        if ($persist == true) {
            $session = Session::getSession();
            $session->refreshId();
            $this->persistCurrentUser();
        }
    }

    /**
     * Writes the current user to the session
     **/
    public function persistCurrentUser()
    {
        $session = Session::getSession();
        $session->set('user', $this->user);
        $session->write();
    }

    /**
     * Tries to authenticate the user with the current session
     **/
    public function authenticateFromSession()
    {
        $this->user = Session::getSession()->get('user');
    }

    /**
     * Returns true when the user is currently authenticated
     *
     * @param  Boolean  $ignoreSession  Set to true to prevent authentication by session
     *
     * @return bool
     */
    public function isAuthenticated($ignoreSession = false)
    {
        if ($this->user === null && !$ignoreSession) {
            $this->authenticateFromSession();
        }
        return is_object($this->user);
    }

    /**
     * Whether an authenticated user has a given permission
     *
     * This is true if the user owns this permission, false if not.
     * Also false if there is no authenticated user
     *
     * TODO: I'd like to see wildcard support, e.g. module/*
     *
     * @param  string  $permission  Permission name
     * @return bool
     */
    public function hasPermission($permission)
    {
        if (! $this->isAuthenticated()) {
            return false;
        }
        foreach ($this->user->getPermissions() as $p) {
            if ($p === $permission) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get applied restrictions matching a given restriction name
     *
     * Returns a list of applied restrictions, empty if no user is
     * authenticated
     *
     * @param  string  $restriction  Restriction name
     * @return array
     */
    public function getRestrictions($restriction)
    {
        if (! $this->isAuthenticated()) {
            return array();
        }
        return $this->user->getRestrictions($restriction);
    }

    /**
     * Purges the current authorization information and removes the user from the session
     **/
    public function removeAuthorization()
    {
        $this->user = null;
        $this->persistCurrentUser();
    }

    /**
     * Returns the current user or null if no user is authenticated
     *
     * @return User
     **/
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Getter for groups belonged to authenticated user
     *
     * @return  array
     * @see     User::getGroups
     **/
    public function getGroups()
    {
        return $this->user->getGroups();
    }

    /**
     * Tries to authenticate the user from the session, and then from the REMOTE_USER superglobal, that can be set by
     * an external authentication provider.
     */
    public function authenticateFromRemoteUser()
    {
        if (array_key_exists('REMOTE_USER', $_SERVER)) {
            $this->fromRemoteUser = true;
        }
        $this->authenticateFromSession();
        if ($this->user !== null) {
            if (array_key_exists('REMOTE_USER', $_SERVER) && $this->user->getUsername() !== $_SERVER["REMOTE_USER"]) {
                // Remote user has changed, clear all sessions
                $this->removeAuthorization();
            }
            return;
        }
        if (array_key_exists('REMOTE_USER', $_SERVER) && $_SERVER["REMOTE_USER"]) {
            $this->user = new User($_SERVER["REMOTE_USER"]);
            $this->persistCurrentUser();
        }
    }

    /**
     * If the session was established from the REMOTE_USER server variable.
     */
    public function isAuthenticatedFromRemoteUser()
    {
        return $this->fromRemoteUser;
    }
}
