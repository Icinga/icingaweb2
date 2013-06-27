<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication\Backend;

use Icinga\Authentication\User as User;
use Icinga\Authentication\UserBackend;
use Icinga\Authentication\Credentials;
use Icinga\Protocol\Ldap;
use Icinga\Application\Config;

/**
*   User authentication backend (@see Icinga\Authentication\UserBackend) for
*   authentication of users via LDAP. The attributes and location of the 
*   user is configurable via the application.ini
*
*   See the UserBackend class (@see Icinga\Authentication\UserBackend) for
*   usage information
**/
class LdapUserBackend implements UserBackend
{
    /**
    *   @var Ldap\Connection
    **/
    protected $connection;
   
    /**
    *   Creates a new Authentication backend using the 
    *   connection information provided in $config
    *
    *   @param object $config   The ldap connection information
    **/
    public function __construct($config)
    {
        $this->connection = new Ldap\Connection($config);
    }

    /**
    *   @see Icinga\Authentication\UserBackend::hasUsername
    **/
    public function hasUsername(Credentials $credential)
    {
        return $this->connection->fetchOne(
            $this->selectUsername($credential->getUsername())
        ) === $credential->getUsername();
    }

    /**
    *   Removes the '*' characted from $string
    *   
    *   @param String $string
    *
    *   @return String
    **/
    protected function stripAsterisks($string)
    {
        return str_replace('*', '', $string);
    }

    /**
    *   Tries to fetch the username given in $username from
    *   the ldap connection, using the configuration parameters 
    *   given in the Authentication configuration
    *
    *   @param  String  $username       The username to select
    *
    *   @return object  $result 
    **/
    protected function selectUsername($username)
    {
        return $this->connection->select()
            ->from(
                Config::getInstance()->authentication->users->user_class,
                array(
                    Config::getInstance()->authentication->users->user_name_attribute
                )
            )
            ->where(
                Config::getInstance()->authentication->users->user_name_attribute,
                $this->stripAsterisks($username)
            );
    }

    /**
    *   @see Icinga\Authentication\UserBackend::authenticate
    **/
    public function authenticate(Credentials $credentials)
    {
        if (!$this->connection->testCredentials(
            $this->connection->fetchDN($this->selectUsername($credentials->getUsername())),
            $credentials->getPassword()
        )
        ) {
            return false;
        }
        $user = new User($credentials->getUsername());

        return $user;
    }
}
