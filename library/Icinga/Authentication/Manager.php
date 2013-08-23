<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 * 
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication;

use \Icinga\Application\Logger;
use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Application\DbAdapterFactory;
use \Icinga\Exception\ConfigurationError as ConfigError;
use \Icinga\User;

/**
 *   The authentication manager allows to identify users and
 *   to persist authentication information in a session.
 *
 *   Direct instanciation is not permitted, the Authencation manager
 *   must be created using the getInstance method. Subsequent getInstance
 *   calls return the same object and ignore any additional configuration
 *
 *   When creating the Authentication manager with standard PHP Sessions,
 *   you have to decide whether you want to modify the session on the first
 *   initialization and provide the 'writeSession' option if so, otherwise
 *   session changes won't be written to disk. This is done to prevent PHP
 *   from blockung concurrent requests
 *
 *   @TODO: Group support is not implemented yet
 **/
class Manager
{
    const BACKEND_TYPE_USER = "User";
    const BACKEND_TYPE_GROUP = "Group";

    /**
     * @var Manager
     **/
    private static $instance = null;

    /**
     * @var User
     **/
    private $user = null;
    private $groups = array();

    /**
     * @var UserBackend
     **/
    private $userBackend = null;

    /**
     * @var GroupBackend
     **/
    private $groupBackend = null;

    /**
     * @var Session
     **/
    private $session = null;

    /**
     * Creates a new authentication manager using the provided config (or the
     * configuration provided in the authentication.ini if no config is given)
     * and with the given options.
     *
     * @param  IcingaConfig    $config     The configuration to use for authentication
     *                                       instead of the authentication.ini
     * @param  Array           $options    Additional options that affect the managers behaviour.
     *                                       Supported values:
     *                                       * writeSession : Whether the session should be writable
     *                                       * userBackendClass : Allows to provide an own user backend class
     *                                         (used for testing)
     *                                       * groupBackendClass : Allows to provide an own group backend class
     *                                         (used for testing)
     *                                       * sessionClass : Allows to provide a different session implementation)
     **/
    private function __construct($config = null, array $options = array())
    {
        if ($config === null) {
            $config = IcingaConfig::app('authentication');
        }
        if (isset($options["userBackendClass"])) {
            $this->userBackend = $options["userBackendClass"];
        } else {
            $this->userBackend = $this->initBestBackend(self::BACKEND_TYPE_USER, $config);
        }

        if (isset($options["groupBackendClass"])) {
            $this->groupBackend = $options["groupBackendClass"];
        } else {
            // @TODO(mh): Re-enable when ready (#4624)
            // Deactivated, logging error messages breaks bootstrap
            // $this->groupBackend = $this->initBestBackend(self::BACKEND_TYPE_GROUP, $config);
        }

        if (!isset($options["sessionClass"])) {
            $this->session = new PhpSession($config->session);
        } else {
            $this->session = $options["sessionClass"];
        }
        if (isset($options["writeSession"]) && $options["writeSession"] === true) {
            $this->session->read(true);
        } else {
            $this->session->read();
        }
    }

    /**
     * @see Manager:__construct()
     **/
    public static function getInstance($config = null, array $options = array())
    {
        if (self::$instance === null) {
            self::$instance = new Manager($config, $options);
        }
        return self::$instance;
    }

    /**
     * Clear the instance (this is mostly needed for testing and shouldn't be called otherwise)
     **/
    public static function clearInstance()
    {
        self::$instance = null;
    }

    /**
     * Create a connection to the best available backend
     *
     * @param  String  $target      "User" or "Group", depending on what
     *                                authentication information the backend should provide
     * @param  Mixed   $backends    The configuration containing all backend configurations
     *                                in falling priority
     *
     * @return (null|UserBackend|GroupBackend)
     */
    private function initBestBackend($target, $backends)
    {
        foreach ($backends as $backend) {
            if (strtolower($target) === strtolower($backend->target)) {
                $db = $this->tryToInitBackend($target, $backend);
                if (isset($db)) {
                    break;
                }
            }
        }
        if (!isset($db)) {
            $msg = 'Failed to create any authentication backend, login will not be possible.';
            Logger::error($msg);
            return null;
        }
        return $db;
    }

    /**
     * Try to create the backend with the given configuration
     *
     * @param   String  $target     "User" or "Group", depending on what
     *                                authentication information the backend should provide
     * @param   $backendConfig      The configuration containing backend description
     *
     * @return  UserBackend|null    Return the created backend or null
     */
    private function tryToInitBackend($target, $backendConfig)
    {
        $type = ucwords(strtolower($backendConfig->backend));
        if (!$type) {
            return null;
        }
        try {
            if ($backendConfig->backend === 'db') {
                $resource = DbAdapterFactory::getDbAdapter($backendConfig->resource);
            } else {
                $resource = $backendConfig;
            }
            $class = '\\Icinga\\Authentication\\Backend\\' . $type . $target. 'Backend';
            return new $class($resource);
        } catch (\Exception $e) {
            $msg = 'Not able to create backend: ' .
                print_r($backendConfig->backend, true)
                . '. Exception: ' . $e->getMessage();
            Logger::warn($msg);
            return null;
        }
    }

    /**
     * Try to authenticate the current user with the Credentials (@see Credentials).
     *
     * @param Credentials  $credentials        The credentials to use for authentication
     * @param Boolean      $persist            Whether to persist the authentication result
     *                                           in the current session
     *
     * @return Boolean                         true on success, otherwise false
     **/
    public function authenticate(Credentials $credentials, $persist = true)
    {
        if (!$this->userBackend) {
            Logger::error("No authentication backend provided, your users will never be able to login.");
            throw new ConfigError(
                "No authentication backend set - login will never succeed as icinga-web ".
                "doesn't know how to determine your user. \n".
                "To fix this error, setup your authentication.ini with a valid authentication backend."
            );
            return false;
        }
        if (!$this->userBackend->hasUsername($credentials)) {
            Logger::info("Unknown user %s tried to log in", $credentials->getUsername());
            return false;
        }
        $this->user = $this->userBackend->authenticate($credentials);
        if ($this->user == null) {
            Logger::info("Invalid credentials for user %s provided", $credentials->getUsername());
            return false;
        }

        if ($persist == true) {
            $this->persistCurrentUser();
            $this->session->write();
        }
        return true;
    }


    /**
     * Writes the current user to the session (only usable when writeSession = true)
     **/
    public function persistCurrentUser()
    {
        $this->session->set("user", $this->user);
    }
    
    /**
     * Tries to authenticate the user with the current session
     **/
    public function authenticateFromSession()
    {
        $this->user = $this->session->get("user", null);
    }

    /**
     * Returns true when the user is currently authenticated
     *
     * @param  Boolean    $ignoreSession      Set to true to prevent authentication by session
     *
     * @param  Boolean
     **/
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
     *   Returns the current user or null if no user is authenticated
     *
     *   @return User
     **/
    public function getUser()
    {
        return $this->user;
    }

    /**
     *   @see User::getGroups
     **/
    public function getGroups()
    {
        return $this->user->getGroups();
    }

    public function getSession()
    {
        return $this->session;
    }
}
