<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
     * Create new authentication backend of type "external"
     *
     * @param ConfigObject $config
     */
    public function __construct(ConfigObject $config)
    {
        $this->stripUsernameRegexp = $config->get('strip_username_regexp');
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * {@inheritdoc}
     */
    public function authenticate(User $user, $password = null)
    {
        $username = getenv('REMOTE_USER');
        if ($username !== false) {
            $user->setExternalUserInformation($username, 'REMOTE_USER');

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
