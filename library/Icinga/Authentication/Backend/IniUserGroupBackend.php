<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication\Backend;

use Icinga\Application\Config;
use Icinga\Authentication\UserGroupBackend;
use Icinga\Exception\ConfigurationError;
use Icinga\User;
use Icinga\Util\String;

/**
 * INI user group backend
 */
class IniUserGroupBackend extends UserGroupBackend
{
    /**
     * Config
     *
     * @var Config
     */
    private $config;

    /**
     * Create a new INI user group backend
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * (non-PHPDoc)
     * @see UserGroupBackend::getMemberships() For the method documentation.
     */
    public function getMemberships(User $user)
    {
        $username = strtolower($user->getUsername());
        $groups = array();
        foreach ($this->config as $name => $section) {
            if (empty($section->users)) {
                throw new ConfigurationError(
                    'Membership section \'%s\' in \'%s\' is missing the \'users\' section',
                    $name,
                    $this->config->getConfigFile()
                );
            }
            if (empty($section->groups)) {
                throw new ConfigurationError(
                    'Membership section \'%s\' in \'%s\' is missing the \'groups\' section',
                    $name,
                    $this->config->getConfigFile()
                );
            }
            $users = array_map('strtolower', String::trimSplit($section->users));
            if (in_array($username, $users)) {
                $groups = array_merge($groups, array_diff(String::trimSplit($section->groups), $groups));
            }
        }
        return $groups;
    }
}
