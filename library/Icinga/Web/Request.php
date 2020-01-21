<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Util\Json;
use Zend_Controller_Request_Http;
use Icinga\Application\Icinga;
use Icinga\User;

/**
 * A request
 */
class Request extends Zend_Controller_Request_Http
{
    /**
     * Response
     *
     * @var Response
     */
    protected $response;

    /**
     * Unique identifier
     *
     * @var string
     */
    protected $uniqueId;

    /**
     * Request URL
     *
     * @var Url
     */
    protected $url;

    /**
     * User if authenticated
     *
     * @var User|null
     */
    protected $user;

    /**
     * Get the response
     *
     * @return Response
     */
    public function getResponse()
    {
        if ($this->response === null) {
            $this->response = Icinga::app()->getResponse();
        }

        return $this->response;
    }

    /**
     * Get the request URL
     *
     * @return Url
     */
    public function getUrl()
    {
        if ($this->url === null) {
            $this->url = Url::fromRequest($this);
        }
        return $this->url;
    }

    /**
     * Get the user if authenticated
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the authenticated user
     *
     * @param   User $user
     *
     * @return  $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get whether the request seems to be an API request
     *
     * @return bool
     */
    public function isApiRequest()
    {
        return $this->getHeader('Accept') === 'application/json';
    }

    /**
     * Makes an ID unique to this request, to prevent id collisions in different containers
     *
     * Call this whenever an ID might show up multiple times in different containers. This function is useful
     * for ensuring unique ids on sites, even if we combine the HTML of different requests into one site,
     * while still being able to reference elements uniquely in the same request.
     *
     * @param   string  $id
     *
     * @return  string  The id suffixed w/ an identifier unique to this request
     */
    public function protectId($id)
    {
        return $id . '-' . Window::getInstance()->getContainerId();
    }

    public function getPost($key = null, $default = null)
    {
        if ($key === null && $this->extractMediaType($this->getHeader('Content-Type')) === 'application/json') {
            return Json::decode(file_get_contents('php://input'), true);
        }

        return parent::getPost($key, $default);
    }

    /**
     * Extract and return the media type from the given header value
     *
     * @param   string  $headerValue
     *
     * @return  string
     */
    protected function extractMediaType($headerValue)
    {
        // Pretty basic and does not care about parameters
        $parts = explode(';', $headerValue, 2);
        return strtolower(trim($parts[0]));
    }
}
