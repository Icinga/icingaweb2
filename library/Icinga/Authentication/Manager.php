<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2014 Icinga Development Team
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
 * @copyright  2014 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication;

use Exception;
use Zend_Config;
use Icinga\User;
use Icinga\Web\Session;
use Icinga\Data\ResourceFactory;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Icinga\Application\Config as IcingaConfig;
use Icinga\Authentication\Backend\DbUserBackend;
use Icinga\Authentication\Backend\LdapUserBackend;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;

/**
 * The authentication manager allows to identify users and
 * to persist authentication information in a session.
 *
 * Direct instantiation is not permitted, the AuthenticationManager
 * must be created using the getInstance method. Subsequent getInstance
 * calls return the same object and ignore any additional configuration.
 **/
class Manager
{
    /**
     * Singleton instance
     *
     * @var self
     */
    private static $instance;

    /**
     * Instance of authenticated user
     *
     * @var User
     **/
    private $user;

    /**
     * Array of user backends
     *
     * @var array
     **/
    private $userBackends = array();

    /**
     * The configuration
     *
     * @var Zend_Config
     */
    private $config = null;

    /**
     * Creates a new authentication manager using the provided config (or the
     * configuration provided in the authentication.ini if no config is given).
     *
     * @param  Zend_Config      $config     The configuration to use for authentication
     *                                      instead of the authentication.ini
     **/
    private function __construct(Zend_Config $config = null)
    {
        if ($config !== null) {
            $this->setupBackends($config);
            $this->config = $config;
        }
    }

    /**
     * Get the authentication manager
     *
     * @param   Zend_Config $config
     *
     * @return  self
     * @see     Manager:__construct
     */
    public static function getInstance(Zend_Config $config = null)
    {
        if (self::$instance === null) {
            self::$instance = new static($config);
        }
        return self::$instance;
    }

    /**
     * Initialize multiple backends from Zend Config
     */
    private function setupBackends(Zend_Config $config)
    {
        foreach ($config as $name => $backendConfig) {
            if ((bool) $backendConfig->get('disabled', false) === true) {
                continue;
            }
            if ($backendConfig->name === null) {
                $backendConfig->name = $name;
            }
            $backend = $this->createBackend($backendConfig);
            $this->userBackends[$backend->getName()] = $backend;
        }
    }

    /**
     * Create a backend from the given Zend_Config
     *
     * @param   Zend_Config $backendConfig
     *
     * @return  UserBackend
     * @throws  ConfigurationError
     */
    private function createBackend(Zend_Config $backendConfig)
    {
        if (isset($backendConfig->class)) {
            // Use a custom backend class, this is only useful for testing
            if (!class_exists($backendConfig->class)) {
                throw new ConfigurationError(
                    'Authentication configuration for backend "' . $backendConfig->name . '" defines an invalid backend'
                    . ' class. Backend class "' . $backendConfig->class. '" not found'
                );
            }
            return new $backendConfig->class($backendConfig);
        }
        if (($type = ResourceFactory::getResourceConfig($backendConfig->resource)->type) === null) {
            throw new ConfigurationError(
                'Authentication configuration for backend "%s" is missing the type directive',
                $backendConfig->name,
                $backendConfig->class
            );
        }
        switch (strtolower($type)) {
            case 'db':
                return new DbUserBackend($backendConfig);
            case 'ldap':
                return new LdapUserBackend($backendConfig);
            default:
                throw new ConfigurationError(
                    'Authentication configuration for backend "' . $backendConfig->name. '" defines an invalid backend'
                    . ' type. Backend type "' . $type . '" is not supported'
                );
        }
    }

    /**
     * Add a user backend to the stack
     *
     * @param   UserBackend   $userBackend
     */
    public function addUserBackend(UserBackend $userBackend)
    {
        $this->userBackends[$userBackend->getName()] = $userBackend;
    }

    /**
     * Get a user backend by name
     *
     * @param   string  $name
     *
     * @return  UserBackend|null
     */
    public function getUserBackend($name)
    {
        return (isset($this->userBackends[$name])) ? $this->userBackends[$name] : null;
    }

    /**
     * Find the backend which provides the user with the given credentials
     *
     * @param   Credential $credentials
     *
     * @return  UserBackend|null
     * @throws  ConfigurationError
     */
    private function revealBackend(Credential $credentials)
    {
        if (count($this->userBackends) === 0) {
            throw new ConfigurationError(
                'No authentication methods available. It seems that none authentication method has been set up. '
                . ' Please contact your Icinga Web administrator'
            );
        }
        $backendsWithError = 0;
        // TODO(el): Currently the user is only notified about authentication backend problems when all backends
        // have errors. It may be the case that the authentication backend which provides the user has errors but other
        // authentication backends work. In that scenario the user is presented an error message saying "Incorrect
        // username or password". We must inform the user that not all authentication methods are available.
        foreach ($this->userBackends as $backend) {
            Logger::debug(
                'Asking authentication backend "%s" for user "%s"',
                $backend->getName(),
                $credentials->getUsername()
            );
            try {
                $hasUser = $backend->hasUsername($credentials);
            } catch (Exception $e) {
                Logger::error(
                    'Cannot ask authentication backend "%s" for user "%s". An exception was thrown: %s',
                    $backend->getName(),
                    $credentials->getUsername(),
                    $e->getMessage()
                );
                ++$backendsWithError;
                continue;
            }
            if ($hasUser === true) {
                Logger::debug(
                    'Authentication backend "%s" provides user "%s"',
                    $backend->getName(),
                    $credentials->getUsername()
                );
                return $backend;
            } else {
                Logger::debug(
                    'Authentication backend "%s" does not provide user "%s"',
                    $backend->getName(),
                    $credentials->getUsername()
                );
            }
        }
        if ($backendsWithError === count($this->userBackends)) {
            throw new ConfigurationError(
                'No authentication methods available. It seems that all set up authentication methods have errors. '
                . ' Please contact your Icinga Web administrator'
            );
        }
        return null;
    }

    /**
     * Try to authenticate a user with the given credentials
     *
     * @param   Credential  $credentials    The credentials to use for authentication
     * @param   Boolean     $persist        Whether to persist the authentication result in the current session
     *
     * @return  Boolean                     Whether the authentication was successful or not
     * @throws  ConfigurationError
     */
    public function authenticate(Credential $credentials, $persist = true)
    {
        $userBackend = $this->revealBackend($credentials);
        if ($userBackend === null) {
            Logger::info('Unknown user "%s" tried to log in', $credentials->getUsername());
            return false;
        }
        if (($user = $userBackend->authenticate($credentials)) === null) {
            Logger::info('User "%s" tried to log in with an incorrect password', $credentials->getUsername());
            return false;
        }

        $username = $credentials->getUsername();

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

        if (($preferencesConfig = IcingaConfig::app()->preferences) !== null) {
            try {
                $preferencesStore = PreferencesStore::create(
                    $preferencesConfig,
                    $user
                );
                $preferences = new Preferences($preferencesStore->load());
            } catch (NotReadableError $e) {
                Logger::error($e);
                $preferences = new Preferences();
            }
        } else {
            $preferences = new Preferences();
        }
        $user->setPreferences($preferences);
        $this->user = $user;
        if ($persist == true) {
            $this->persistCurrentUser();
        }

        Logger::info('User "%s" logged in', $credentials->getUsername());

        return true;
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
}
