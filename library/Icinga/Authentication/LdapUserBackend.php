<?php

namespace Icinga\Authentication;

use Icinga\Protocol\Ldap;

class LdapUserBackend extends UserBackend
{
    protected $connection;

    protected function init()
    {
        $this->connection = new Ldap\Connection($this->config);
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
        $user = User::create(
            $this,
            array(
                'username' => $username,
            )
        );
        return $user;
    }
}
