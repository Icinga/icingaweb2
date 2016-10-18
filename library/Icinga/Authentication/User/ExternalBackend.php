<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\User;

use Icinga\Data\ConfigObject;
use Icinga\User;

/**
 * Test login with external authentication mechanism, e.g. Apache
 */
class ExternalBackend implements UserBackendInterface
{
    /**
     * The name of this backend
     *
     * @var string
     */
    protected $name;

    /**
     * Regexp expression to strip values from a username
     *
     * @var string
     */
    protected $stripUsernameRegexp;

    /**
     * The name variable where to read the user from
     *
     * @var string|null
     */
    protected $usernameEnvvar;

    /**
     * Create new authentication backend of type "external"
     *
     * @param ConfigObject $config
     */
    public function __construct(ConfigObject $config)
    {
        $this->stripUsernameRegexp = $config->get('strip_username_regexp');
        $this->usernameEnvvar = $config->get('username_envvar');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the remote user from environment or $_SERVER, if any
     *
     * @param   string|null $variable   The name variable where to read the user from
     *
     * @return  string|null
     */
    public static function getRemoteUser($variable = 'REMOTE_USER')
    {
        foreach (($variable === null ? array('REMOTE_USER', 'REDIRECT_REMOTE_USER') : array($variable)) as $variable) {
            $username = getenv($variable);
            if ($username !== false) {
                return $username;
            }
            if (array_key_exists($variable, $_SERVER)) {
                return $_SERVER[$variable];
            }
        }
        return null;
    }


    /**
     * {@inheritdoc}
     */
    public function authenticate(User $user, $password = null)
    {
        $username = static::getRemoteUser($this->usernameEnvvar);
        if ($username !== null) {
            $user->setExternalUserInformation($username, $this->usernameEnvvar);

            if ($this->stripUsernameRegexp) {
                $stripped = preg_replace($this->stripUsernameRegexp, '', $username);
                if ($stripped !== false) {
                    // TODO(el): PHP issues a warning when PHP cannot compile the regular expression. Should we log an
                    // additional message in that case?
                    $username = $stripped;
                }
            }

            $user->setUsername($username);
            return true;
        }

        return false;
    }
}
