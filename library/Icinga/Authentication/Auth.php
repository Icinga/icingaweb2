<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\User\ExternalBackend;
use Icinga\Authentication\UserGroup\UserGroupBackend;
use Icinga\Data\ConfigObject;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotReadableError;
use Icinga\User;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Web\Session;

class Auth
{
    /**
     * Singleton instance
     *
     * @var self
     */
    private static $instance;

    /**
     * Request
     *
     * @var \Icinga\Web\Request
     */
    protected $request;

    /**
     * Response
     *
     * @var \Icinga\Web\Response
     */
    protected $response;

    /**
     * Authenticated user
     *
     * @var User
     */
    private $user;


    /**
     * @see getInstance()
     */
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
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the auth chain
     *
     * @return AuthChain
     */
    public function getAuthChain()
    {
        return new AuthChain();
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
        if ($this->user === null && ! $this->authExternal()) {
            return $this->authHttp();
        }
        return true;
    }

    public function setAuthenticated(User $user, $persist = true)
    {
        $username = $user->getUsername();
        try {
            $config = Config::app();
        } catch (NotReadableError $e) {
            Logger::error(
                new IcingaException(
                    'Cannot load preferences for user "%s". An exception was thrown: %s',
                    $username,
                    $e
                )
            );
            $config = new Config();
        }
        if ($config->get('global', 'config_backend', 'ini') !== 'none') {
            $preferencesConfig = new ConfigObject(array(
                'store'     => $config->get('global', 'config_backend', 'ini'),
                'resource'  => $config->get('global', 'config_resource')
            ));
            try {
                $preferencesStore = PreferencesStore::create(
                    $preferencesConfig,
                    $user
                );
                $preferences = new Preferences($preferencesStore->load());
            } catch (Exception $e) {
                Logger::error(
                    new IcingaException(
                        'Cannot load preferences for user "%s". An exception was thrown: %s',
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
        $groups = $user->getGroups();
        foreach (Config::app('groups') as $name => $config) {
            try {
                $groupBackend = UserGroupBackend::create($name, $config);
                $groupsFromBackend = $groupBackend->getMemberships($user);
            } catch (Exception $e) {
                Logger::error(
                    'Can\'t get group memberships for user \'%s\' from backend \'%s\'. An exception was thrown: %s',
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
        list($permissions, $restrictions) = $admissionLoader->getPermissionsAndRestrictions($user);
        $user->setPermissions($permissions);
        $user->setRestrictions($restrictions);
        $this->user = $user;
        if ($persist) {
            $this->persistCurrentUser();
        }
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

    /**
     * Get the request
     *
     * @return \Icinga\Web\Request
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $this->request = Icinga::app()->getRequest();
        }
        return $this->request;
    }

    /**
     * Get the response
     *
     * @return \Icinga\Web\Response
     */
    public function getResponse()
    {
        if ($this->response === null) {
            $this->response = Icinga::app()->getResponse();
        }
        return $this->response;
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
     * Returns the current user or null if no user is authenticated
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
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
        if ($this->user !== null && $this->user->isExternalUser() === true) {
            list($originUsername, $field) = $this->user->getExternalUserInformation();
            if (! array_key_exists($field, $_SERVER) || $_SERVER[$field] !== $originUsername) {
                $this->removeAuthorization();
            }
        }
    }

    /**
     * Attempt to authenticate a user from external user backends
     *
     * @return bool
     */
    protected function authExternal()
    {
        $user = new User('');
        foreach ($this->getAuthChain() as $userBackend) {
            if ($userBackend instanceof ExternalBackend) {
                if ($userBackend->authenticate($user)) {
                    $this->setAuthenticated($user);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Attempt to authenticate a user using HTTP authentication
     *
     * Supports only the Basic HTTP authentication scheme. XHR will be ignored.
     *
     * @return bool
     */
    protected function authHttp()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return false;
        }
        if (($header = $this->getRequest()->getHeader('Authorization')) === false) {
            return false;
        }
        if (empty($header)) {
            $this->challengeHttp();
        }
        list($scheme) = explode(' ', $header, 2);
        if ($scheme !== 'Basic') {
            $this->challengeHttp();
        }
        $authorization = substr($header, strlen('Basic '));
        $credentials = base64_decode($authorization);
        $credentials = array_filter(explode(':', $credentials, 2));
        if (count($credentials) !== 2) {
            // Deny empty username and/or password
            $this->challengeHttp();
        }
        $user = new User($credentials[0]);
        $password = $credentials[1];
        if ($this->getAuthChain()->setSkipExternalBackends(true)->authenticate($user, $password)) {
            $this->setAuthenticated($user, false);
            $user->setIsHttpUser(true);
            return true;
        } else {
            $this->challengeHttp();
        }
    }

    /**
     * Challenge client immediately for HTTP authentication
     *
     * Sends the response w/ the 401 Unauthorized status code and WWW-Authenticate header.
     */
    protected function challengeHttp()
    {
        $response = $this->getResponse();
        $response->setHttpResponseCode(401);
        $response->setHeader('WWW-Authenticate', 'Basic realm="Icinga Web 2"');
        $response->sendHeaders();
        exit();
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
     * Writes the current user to the session
     */
    public function persistCurrentUser()
    {
        Session::getSession()->set('user', $this->user)->refreshId();
    }

    /**
     * Purges the current authorization information and session
     */
    public function removeAuthorization()
    {
        $this->user = null;
        Session::getSession()->purge();
    }
}
