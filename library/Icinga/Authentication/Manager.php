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

namespace Icinga\Authentication;

use \Exception;
use Icinga\Exception\ConfigurationError;
use \Zend_Config;
use \Icinga\User;
use \Icinga\Data\ResourceFactory;
use \Icinga\Application\Logger;
use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Authentication\Backend\DbUserBackend;
use \Icinga\Authentication\Backend\LdapUserBackend;
use \Icinga\Exception\ConfigurationError as ConfigError;


/**
 *   The authentication manager allows to identify users and
 *   to persist authentication information in a session.
 *
 *   Direct instantiation is not permitted, the AuthenticationManager
 *   must be created using the getInstance method. Subsequent getInstance
 *   calls return the same object and ignore any additional configuration
 *
 *   When creating the Authentication manager with standard PHP Sessions,
 *   you have to decide whether you want to modify the session on the first
 *   initialization and provide the 'writeSession' option if so, otherwise
 *   session changes won't be written to disk. This is done to prevent PHP
 *   from blocking concurrent requests
 *
 *   @TODO(mh): Group support is not implemented yet (#4624)
 **/
class Manager
{
    /**
     * Singleton instance
     *
     * @var self
     */
    private static $instance = null;

    /**
     * Instance of authenticated user
     *
     * @var User
     **/
    private $user = null;

    /**
     * Array of user backends
     *
     * @var UserBackend[]
     **/
    private $userBackends = array();

    /**
     * Array of group backends
     *
     * @var array
     **/
    private $groupBackends = array();

    /**
     * Session
     *
     * @var Session
     **/
    private $session = null;

    /**
     * The configuration
     *
     * @var Zend_Config
     */
    private $config = null;

    /**
     * If the backends are already created.
     *
     * @var Boolean
     */
    private $initialized = false;

    /**
     * Creates a new authentication manager using the provided config (or the
     * configuration provided in the authentication.ini if no config is given)
     * and with the given options.
     *
     * @param  Zend_Config      $config     The configuration to use for authentication
     *                                      instead of the authentication.ini
     * @param  array            $options    Additional options that affect the managers behaviour.
     *                                      Supported values:
     *                                      * writeSession: Whether the session should be writable
     *                                      * sessionClass: Allows to provide a different session implementation)
     *                                      * noDefaultConfig: Disable default configuration from authentication.ini
     **/
    private function __construct(Zend_Config $config = null, array $options = array())
    {
        if ($config === null && !(isset($options['noDefaultConfig']) && $options['noDefaultConfig'] == true)) {
                $config = IcingaConfig::app('authentication');
        }
        if (!isset($options['sessionClass'])) {
            $this->session = new PhpSession();
        } else {
            $this->session = $options['sessionClass'];
        }
        $this->config = $config;
    }

    /**
     * Get a singleton instance of our self
     *
     * @param   Zend_Config $config
     * @param   array       $options
     *
     * @return  self
     * @see     Manager:__construct
     */
    public static function getInstance(Zend_Config $config = null, array $options = array())
    {
        if (self::$instance === null) {
            self::$instance = new Manager($config, $options);
        }
        return self::$instance;
    }

    /**
     * Initialize multiple backends from Zend Config
     */
    private function setupBackends(Zend_Config $config)
    {
        foreach ($config as $name => $backendConfig) {
            // We won't initialize disabled backends
            if ($backendConfig->get('disabled') == '1') {
                continue;
            }

            if ($backendConfig->name === null) {
                $backendConfig->name = $name;
            }
            $backend = $this->createBackend($backendConfig);
            if ($backend instanceof UserBackend) {
                $backend->connect();
                $this->userBackends[$backend->getName()] = $backend;
            } elseif ($backend instanceof GroupBackend) {
                $backend->connect();
                $this->groupBackends[$backend->getName()] = $backend;
            }
        }
    }

    /**
     * Create a single backend from Zend Config
     *
     * @param   Zend_Config $backendConfig
     *
     * @return  null|UserBackend
     */
    private function createBackend(Zend_Config $backendConfig)
    {
        $target = ucwords(strtolower($backendConfig->target));
        $name = $backendConfig->name;
        // TODO: implement support for groups (#4624) and remove OR-Clause
        if ((!$target || strtolower($target) != "user") && !$backendConfig->class) {
            Logger::warn('AuthManager: Backend "%s" has no target configuration. (e.g. target=user|group)', $name);
            return null;
        }
        try {
            if (isset($backendConfig->class)) {
                // use a custom backend class, this is probably only useful for testing
                if (!class_exists($backendConfig->class)) {
                    Logger::error('AuthManager: Class not found (%s) for backend %s', $backendConfig->class, $name);
                    return null;
                }
                $class = $backendConfig->class;
                return new $class($backendConfig);
            }

            switch (ResourceFactory::getResourceConfig($backendConfig->resource)->type) {
                case 'db':
                    return new DbUserBackend($backendConfig);
                case 'ldap':
                    return new LdapUserBackend($backendConfig);
                default:
                    Logger::warn('AuthManager: Resource type ' . $backendConfig->type . ' not available.');
            }
        } catch (\Exception $e) {
            Logger::warn('AuthManager: Not able to create backend. Exception was thrown: %s', $e->getMessage());
        }
        return null;
    }

    /**
     * Add a user backend to stack
     *
     * @param UserBackend $userBackend
     */
    public function addUserBackend(UserBackend $userBackend)
    {
        $this->userBackends[$userBackend->getName()] = $userBackend;
    }

    /**
     * Get a user backend by name
     *
     * @param   string $name
     *
     * @return  UserBackend|null
     */
    public function getUserBackend($name)
    {
        $this->initBackends();
        return (isset($this->userBackends[$name])) ?
            $this->userBackends[$name] : null;
    }

    /**
     * Add a group backend to stack
     *
     * @param GroupBackend $groupBackend
     */
    public function addGroupBackend(GroupBackend $groupBackend)
    {
        $this->groupBackends[$groupBackend->getName()] = $groupBackend;
    }

    /**
     * Get a group backend by name
     *
     * @param   string $name
     *
     * @return  GroupBackend|null
     */
    public function getGroupBackend($name)
    {
        $this->initBackends();
        return (isset($this->groupBackends[$name])) ?
            $this->groupBackends[$name] : null;
    }

    /**
     * Find a backend for a credential
     *
     * @param   Credential $credentials
     *
     * @return  UserBackend|null
     * @throws  ConfigurationError
     */
    private function getBackendForCredential(Credential $credentials)
    {
        $this->initBackends();

        $authErrors = 0;
        foreach ($this->userBackends as $userBackend) {

            $flag = false;

            try {
                Logger::debug(
                    'AuthManager: Try backend %s for user %s',
                    $userBackend->getName(),
                    $credentials->getUsername()
                );
                $flag = $userBackend->hasUsername($credentials);
            } catch (Exception $e) {
                Logger::error(
                    'AuthManager: Backend "%s" has errors. Exception was thrown: %s',
                    $userBackend->getName(),
                    $e->getMessage()
                );

                $authErrors++;
                continue;
            }

            if ($flag === true) {
                Logger::debug(
                    'AuthManager: Backend %s has user %s',
                    $userBackend->getName(),
                    $credentials->getUsername()
                );
                return $userBackend;
            }
        }

        if ($authErrors >= count($this->userBackends)) {
            Logger::fatal('AuthManager: No working backend found, unable to authenticate any user');
            throw new ConfigurationError(
                'No working backend found. Unable to authenticate any user.'
                . "\n"
                . 'Please examine the logs for more information.'
            );
        }

        return null;
    }

    /**
     * Ensures that all backends are initialized
     */
    private function initBackends()
    {
        if (!$this->initialized) {
            $this->setupBackends($this->config);
            $this->initialized = true;
        }
    }

    /**
     * Try to authenticate the current user with the Credential (@see Credential).
     *
     * @param   Credential $credentials        The credentials to use for authentication
     * @param   Boolean     $persist            Whether to persist the authentication result
     *                                          in the current session
     *
     * @return  Boolean                         true on success, otherwise false
     * @throws  ConfigError
     */
    public function authenticate(Credential $credentials, $persist = true)
    {
        $this->initBackends();
        if (count($this->userBackends) === 0) {
            Logger::error('AuthManager: No authentication backend provided, your users will never be able to login.');
            throw new ConfigError(
                'No authentication backend set - login will never succeed as icinga-web '
                . 'doesn\'t know how to determine your user. ' . "\n"
                . 'To fix this error, setup your authentication.ini with at least one valid authentication backend.'
            );
        }

        $userBackend = $this->getBackendForCredential($credentials);

        if ($userBackend === null) {
            Logger::info('AuthManager: Unknown user %s tried to log in', $credentials->getUsername());
            return false;
        }

        $this->user = $userBackend->authenticate($credentials);

        if ($this->user == null) {
            Logger::info('AuthManager: Invalid credentials for user %s provided', $credentials->getUsername());
            return false;
        }

        if ($persist == true) {
            $this->persistCurrentUser();
            $this->session->write();
        }

        Logger::info('AuthManager: User successfully logged in: %s', $credentials->getUsername());

        return true;
    }

    /**
     * Writes the current user to the session (only usable when writeSession = true)
     **/
    public function persistCurrentUser()
    {
        $this->session->set('user', $this->user);
    }

    /**
     * Tries to authenticate the user with the current session
     **/
    public function authenticateFromSession()
    {
        $this->user = $this->session->get('user', null);
    }

    /**
     * Returns true when the user is currently authenticated
     *
     * @param  Boolean    $ignoreSession      Set to true to prevent authentication by session
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
     * Purges the current authorisation information and deletes the session
     **/
    public function removeAuthorization()
    {
        $this->user = null;
        $this->session->purge();
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
     * Getter for groups belong authenticated user
     *
     * @return  array
     * @see     User::getGroups
     **/
    public function getGroups()
    {
        return $this->user->getGroups();
    }

    /**
     * Getter for session
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }
}
