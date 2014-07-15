<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Zend_Controller_Request_Http;
use Icinga\User;

/**
 * Request to handle special attributes
 */
class Request extends Zend_Controller_Request_Http
{
    /**
     * User object
     *
     * @var User
     */
    private $user;

    /**
     * Setter for user
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Getter for user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
