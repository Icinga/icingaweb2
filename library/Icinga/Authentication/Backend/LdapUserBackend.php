<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication\Backend;

use Icinga\Authentication\User as User;
use Icinga\Authentication\UserBackend;
use Icinga\Authentication\Credentials;
use Icinga\Protocol\Ldap;

class LdapUserBackend implements UserBackend
{
    protected $connection;

    public function __construct($config)
    {
        $this->connection = new Ldap\Connection($config);
    }

    public function hasUsername(Credentials $credential)
    {
        return $this->connection->fetchOne(
            $this->selectUsername($credential->getUsername())
        ) === $credential->getUsername();
    }

    protected function stripAsterisks($string)
    {
        return str_replace('*', '', $string);
    }

    protected function selectUsername($username)
    {
        return $this->connection->select()
            ->from('user', array('sAMAccountName'))
            ->where('sAMAccountName', $this->stripAsterisks($username));
    }

    public function authenticate(Credentials $credentials)
    {
        if (!$this->connection->testCredentials(
            $this->connection->fetchDN($this->selectUsername($credentials->getUsername())),
            $credentials->getPassword()
        )     ) {
            return false;
        }
        $user = new User($credentials->getUsername());

        return $user;
    }
}
