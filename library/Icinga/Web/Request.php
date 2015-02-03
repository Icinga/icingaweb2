<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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

    private $url;

    public function getUrl()
    {
        if ($this->url === null) {
            $this->url = Url::fromRequest($this);
        }
        return $this->url;
    }

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
