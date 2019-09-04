<?php
/* Icinga Web 2 | (c) 2019 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\User;

use Icinga\Application\Config;
use Icinga\User;

/**
 * Test login with external authentication mechanism, e.g. Apache
 */
trait ExternalAuthTrait
{
    /**
     * Possible variables where to read the user from
     *
     * @var string[]
     */
    public static $remoteUserEnvvars = array('REMOTE_USER', 'REDIRECT_REMOTE_USER');

    protected $externalAuth = false;

    /**
     * Get the remote user from environment or $_SERVER, if any
     *
     * @param   string  $variable   The name of the variable where to read the user from
     *
     * @return  string|null
     */
    public static function getRemoteUser($variable = 'REMOTE_USER')
    {
        $username = getenv($variable);
        if ($username !== false) {
            return $username;
        }

        if (array_key_exists($variable, $_SERVER)) {
            return $_SERVER[$variable];
        }

        return null;
    }

    /**
     * Get the remote user information from environment or $_SERVER, if any
     *
     * @return  array   Contains always two entries, the username and origin which may both set to null.
     */
    public static function getRemoteUserInformation()
    {
        foreach (static::$remoteUserEnvvars as $envVar) {
            $username = static::getRemoteUser($envVar);
            if ($username !== null) {
                return [$username, $envVar];
            }
        }

        return [null, null];
    }

    /**
     * Try external authentication and set username
     *
     * The authenticate() function should check hasExternalAuth() and then
     * call this function to implement the support for external auth
     *
     * @param User $user
     * @param bool $updateUser
     *
     * @return array|false with $username and $field
     */
    public function authenticateExternal(User $user, $updateUser = true)
    {
        list($username, $field) = static::getRemoteUserInformation();
        if ($username !== null) {
            if ($updateUser) {
                $user->setExternalUserInformation($username, $field);

                $user->setUsername($username);
                if (! $user->hasDomain()) {
                    $user->setDomain(Config::app()->get('authentication', 'default_domain'));
                }
            }

            return [$username, $field, $user->getUsername()];
        }

        return false;
    }

    /**
     * Enable external auth for the backend
     *
     * @param bool $externalAuth
     *
     * @return $this
     */
    public function setExternalAuth($externalAuth = true)
    {
        $this->externalAuth = (bool) $externalAuth;
        return $this;
    }

    /**
     * Checks if external auth is enabled
     *
     * @return bool
     */
    public function hasExternalAuth()
    {
        return $this->externalAuth;
    }
}
