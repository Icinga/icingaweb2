<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication;

use Exception;
use Zend_Config;
use Icinga\Application\Config;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotReadableError;
use Icinga\Application\Logger;
use Icinga\User;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Web\Session;

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
     */
    private $user;


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
            $config = Config::app();
        } catch (NotReadableError $e) {
            Logger::error(
                new IcingaException(
                    'Cannot load preferences for user "%s". An exception was thrown',
                    $username,
                    $e
                )
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
                    new IcingaException(
                        'Cannot load preferences for user "%s". An exception was thrown',
                        $username,
                        $e
                    )
                );
                $preferences = new Preferences();
            }
        } else {
            $preferences = new Preferences();
        }
        $user->setPreferences($preferences);
        $groups = array();
        foreach (Config::app('groups') as $name => $config) {
            try {
                $groupBackend = UserGroupBackend::create($name, $config);
                $groupsFromBackend = $groupBackend->getMemberships($user);
            } catch (Exception $e) {
                Logger::error(
                    'Can\'t get group memberships for user \'%s\' from backend \'%s\'. An exception was thrown:',
                    $username,
                    $name,
                    $e
                );
                continue;
            }
            if (empty($groupsFromBackend)) {
                continue;
            }
            $groupsFromBackend = array_values($groupsFromBackend);
            $groups = array_merge($groups, array_combine($groupsFromBackend, $groupsFromBackend));
        }
        $user->setGroups($groups);
        $admissionLoader = new AdmissionLoader();
        $user->setPermissions($admissionLoader->getPermissions($user));
        $user->setRestrictions($admissionLoader->getRestrictions($user));
        $this->user = $user;
        if ($persist) {
            $this->persistCurrentUser();
        }
    }

    /**
     * Writes the current user to the session
     */
    public function persistCurrentUser()
    {
        $session = Session::getSession();
        $session->set('user', $this->user);
        $session->write();
        $session->refreshId();
    }

    /**
     * Try to authenticate the user with the current session
     *
     * Authentication for externally-authenticated users will be revoked if the username changed or external
     * authentication is no longer in effect
     */
    public function authenticateFromSession()
    {
        $this->user = Session::getSession()->get('user');
        if ($this->user !== null && $this->user->isRemoteUser() === true) {
            list($originUsername, $field) = $this->user->getRemoteUserInformation();
            if (! array_key_exists($field, $_SERVER) || $_SERVER[$field] !== $originUsername) {
                $this->removeAuthorization();
            }
        }
    }

    /**
     * Whether the user is authenticated
     *
     * @param  bool $ignoreSession True to prevent session authentication
     *
     * @return bool
     */
    public function isAuthenticated($ignoreSession = false)
    {
        if ($this->user === null && ! $ignoreSession) {
            $this->authenticateFromSession();
        }
        return is_object($this->user);
    }

    /**
     * Whether an authenticated user has a given permission
     *
     * @param  string  $permission  Permission name
     *
     * @return bool                 True if the user owns the given permission, false if not or if not authenticated
     */
    public function hasPermission($permission)
    {
        if (! $this->isAuthenticated()) {
            return false;
        }
        return $this->user->can($permission);
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
     * Purges the current authorization information and session
     */
    public function removeAuthorization()
    {
        $this->user = null;
        Session::getSession()->purge();
    }

    /**
     * Returns the current user or null if no user is authenticated
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Getter for groups belonged to authenticated user
     *
     * @return  array
     * @see     User::getGroups
     */
    public function getGroups()
    {
        return $this->user->getGroups();
    }
}
