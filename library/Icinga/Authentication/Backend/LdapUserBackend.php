<?php

namespace Icinga\Authentication\Backend;

use Icinga\Authentication\User as User;
use Icinga\Protocol\Ldap;

class LdapUserBackend implements UserBackend
{
    protected $connection;

    public function __construct($config)
    {
        $this->connection = new Ldap\Connection($config);
    }

    public function hasUsername($username)
    {
        if (! $username) {
            return false;
        }
        return $this->connection->fetchOne(
            $this->selectUsername($username)
        ) === $username;
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

    public function authenticate($username, $password = null)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        if (! $this->connection->testCredentials(
            $this->connection->fetchDN($this->selectUsername($username)),
            $password
        )) {
            return false;
        }
        $user = new User($username);
        return $user;
    }
}
