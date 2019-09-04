<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\User;

use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\User;

/**
 * Test login with external authentication mechanism, e.g. Apache
 */
class ExternalBackend implements UserBackendInterface, ExternalAuthInterface
{
    use ExternalAuthTrait;

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
     * {@inheritdoc}
     */
    public function authenticate(User $user, $password = null)
    {
        $external = $this->authenticateExternal($user);
        if (! $external) {
            return false;
        }

        if ($this->stripUsernameRegexp) {
            $stripped = @preg_replace($this->stripUsernameRegexp, '', $external[0]);
            if ($stripped === false) {
                Logger::error('Failed to strip external username. The configured regular expression is invalid.');
                return false;
            }

            $user->setUsername($stripped);
        }

        return true;
    }
}
