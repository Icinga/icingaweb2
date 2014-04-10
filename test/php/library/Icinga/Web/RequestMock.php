<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web;

/**
 * Request mock that implements all methods required by the Url class
 */
class RequestMock
{
    /**
     * The path of the request
     *
     * @var string
     */
    public $path = "";

    /**
     * The baseUrl of the request
     *
     * @var string
     */
    public $baseUrl = '/';

    /**
     * An array of query parameters that the request should resemble
     *
     * @var array
     */
    public $query = array();

    /**
     * Returns the path set for the request
     *
     * @return string
     */
    public function getPathInfo()
    {
        return $this->path;
    }

    /**
     * Returns the baseUrl set for the request
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Returns the query set for the request
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }
}
