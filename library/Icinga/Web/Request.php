<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
     * @var string
     */
    private $uniqueId;

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

    /**
     * Makes an ID unique to this request, to prevent id collisions in different containers
     *
     * Call this whenever an ID might show up multiple times in different containers. This function is useful
     * for ensuring unique ids on sites, even if we combine the HTML of different requests into one site,
     * while still being able to reference elements uniquely in the same request.
     */
    public function protectId($id)
    {
        if (! isset($this->uniqueId)) {
            $this->uniqueId = Window::generateId();
        }
        return $id . '-' . $this->uniqueId;
    }
}
