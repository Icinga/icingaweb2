<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}}

namespace Icinga\Authentication;

interface UserBackend
{
    /**
     * Creates a new object
     * @param $config
     */
    public function __construct($config);

    /**
     * Test if the username exists
     * @param Credentials $credentials
     * @return boolean
     */
    public function hasUsername(Credentials $credentials);

    /**
     * Authenticate
     * @param Credentials $credentials
     * @return User
     */
    public function authenticate(Credentials $credentials);
}
