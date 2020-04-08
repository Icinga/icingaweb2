<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Hook\AuditHook;
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
     * Get whether the user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        if ($this->user !== null) {
            return true;
        }
        $this->authenticateFromSession();
        if ($this->user === null && ! $this->authExternal()) {
            return false;
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
        // TODO(el): Quick-fix for #10957. Only reload CSS if the theme changed.
        $this->getResponse()->setReloadCss(true);
        $user->setPreferences($preferences);
        $groups = $user->getGroups();
        $userBackendName = $user->getAdditional('backend_name');
        foreach (Config::app('groups') as $name => $config) {
            $groupsUserBackend = $config->user_backend;
            if ($groupsUserBackend
                && $groupsUserBackend !== 'none'
                && $userBackendName !== null
                && $groupsUserBackend !== $userBackendName
            ) {
                // Do not ask for Group membership if a specific User Backend
                // has been assigned to that Group Backend, and the user has
                // been authenticated by another User Backend
                continue;
            }

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
                Logger::debug(
                    'No groups found in backend "%s" which the user "%s" is a member of.',
                    $name,
                    $user->getUsername()
                );
                continue;
            }
            $groupsFromBackend = array_values($groupsFromBackend);
            Logger::debug(
                'Groups found in backend "%s" for user "%s": %s',
                $name,
                $user->getUsername(),
                join(', ', $groupsFromBackend)
            );
            $groups = array_merge($groups, array_combine($groupsFromBackend, $groupsFromBackend));
        }
        $user->setGroups($groups);
        $admissionLoader = new AdmissionLoader();
        $admissionLoader->applyRoles($user);
        $this->user = $user;
        if ($persist) {
            $this->persistCurrentUser();
        }
        AuditHook::logActivity('login', 'User logged in');
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
     * Set the authenticated user
     *
     * Note that this method just sets the authenticated user and thus bypasses our default authentication process in
     * {@link setAuthenticated()}.
     *
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
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
        if ($this->user !== null && $this->user->isExternalUser()) {
            list($originUsername, $field) = $this->user->getExternalUserInformation();
            $username = ExternalBackend::getRemoteUser($field);
            if ($username === null || $username !== $originUsername) {
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
                    if (! $user->hasDomain()) {
                        $user->setDomain(Config::app()->get('authentication', 'default_domain'));
                    }
                    $this->setAuthenticated($user);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Attempt to authenticate a user using HTTP authentication on API requests only
     *
     * Supports only the Basic HTTP authentication scheme. XHR will be ignored.
     *
     * @return bool
     */
    public function authHttp()
    {
        $request = $this->getRequest();
        $header = $request->getHeader('Authorization');
        if (empty($header)) {
            return false;
        }
        list($scheme) = explode(' ', $header, 2);
        if ($scheme !== 'Basic') {
            return false;
        }
        $authorization = substr($header, strlen('Basic '));
        $credentials = base64_decode($authorization);
        $credentials = array_filter(explode(':', $credentials, 2));
        if (count($credentials) !== 2) {
            // Deny empty username and/or password
            return false;
        }
        $user = new User($credentials[0]);
        if (! $user->hasDomain()) {
            $user->setDomain(Config::app()->get('authentication', 'default_domain'));
        }
        $password = $credentials[1];
        if ($this->getAuthChain()->setSkipExternalBackends(true)->authenticate($user, $password)) {
            $this->setAuthenticated($user, false);
            $user->setIsHttpUser(true);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Challenge client immediately for HTTP authentication
     *
     * Sends the response w/ the 401 Unauthorized status code and WWW-Authenticate header.
     */
    public function challengeHttp()
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
        // @TODO(el): https://dev.icinga.com/issues/10646
        $params = session_get_cookie_params();
        setcookie(
            'icingaweb2-session',
            time(),
            null,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
        Session::getSession()->set('user', $this->user)->refreshId();
    }

    /**
     * Purges the current authorization information and session
     */
    public function removeAuthorization()
    {
        AuditHook::logActivity('logout', 'User logged out');
        $this->user = null;
        Session::getSession()->purge();
    }
}
