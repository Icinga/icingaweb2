<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Cli\FakeRequest;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\UrlParams;

/**
 * Url class that provides convenient access to parameters, allows to modify query parameters and
 * returns Urls reflecting all changes made to the url and to the parameters.
 *
 * Direct instantiation is prohibited and should be done either with @see Url::fromRequest() or
 * @see Url::fromUrlString()
 *
 * Currently, protocol, host and port are ignored and will be implemented when required
 */
class Url
{
    /**
     * An array of all parameters stored in this Url
     *
     * @var UrlParams
     */
    protected $params;

    /**
     * The site anchor after the '#'
     *
     * @var string
     */
    protected $anchor = '';

    /**
     * The relative path of this Url, without query parameters
     *
     * @var string
     */
    protected $path = '';

    /**
     * The baseUrl that will be appended to @see Url::$path in order to
     * create an absolute Url
     *
     * @var string
     */
    protected $baseUrl = '/';

    protected function __construct()
    {
        $this->params = UrlParams::fromQueryString(''); // TODO: ::create()
    }

    /**
     * Create a new Url class representing the current request
     *
     * If $params are given, those will be added to the request's parameters
     * and overwrite any existing parameters
     *
     * @param   UrlParams|array $params     Parameters that should additionally be considered for the url
     * @param   Zend_Request    $request    A request to use instead of the default one
     *
     * @return  Url
     */
    public static function fromRequest($params = array(), $request = null)
    {
        if ($request === null) {
            $request = self::getRequest();
        }

        $url = new Url();
        $url->setPath($request->getPathInfo());

        // $urlParams = UrlParams::fromQueryString($request->getQuery());
        if (isset($_SERVER['QUERY_STRING'])) {
            $urlParams = UrlParams::fromQueryString($_SERVER['QUERY_STRING']);
        } else {
            $urlParams = UrlParams::fromQueryString('');
            foreach ($request->getQuery() as $k => $v) {
                $urlParams->set($k, $v);
            }
        }

        foreach ($params as $k => $v) {
            $urlParams->set($k, $v);
        }
        $url->setParams($urlParams);
        $url->setBaseUrl($request->getBaseUrl());
        return $url;
    }

    /**
     * Return a request object that should be used for determining the URL
     *
     * @return  Zend_Abstract_Request
     */
    protected static function getRequest()
    {
        $app = Icinga::app();
        if ($app->isCli()) {
            return new FakeRequest();
        } else {
            return $app->getFrontController()->getRequest();
        }
    }

    /**
     * Create a new Url class representing the given url
     *
     * If $params are given, those will be added to the urls parameters
     * and overwrite any existing parameters
     *
     * @param   string          $url        The string representation of the url to parse
     * @param   array           $params     An array of parameters that should additionally be considered for the url
     * @param   Zend_Request    $request    A request to use instead of the default one
     *
     * @return  Url
     */
    public static function fromPath($url, array $params = array(), $request = null)
    {
        if ($request === null) {
            $request = self::getRequest();
        }

        if (!is_string($url)) {
            throw new ProgrammingError(
                'url "%s" is not a string',
                $url
            );
        }

        $urlObject = new Url();
        $baseUrl = $request->getBaseUrl();
        $urlObject->setBaseUrl($baseUrl);

        $urlParts = parse_url($url);
        if (isset($urlParts['path'])) {
            if ($baseUrl !== '' && strpos($urlParts['path'], $baseUrl) === 0) {
                $urlObject->setPath(substr($urlParts['path'], strlen($baseUrl)));
            } else {
                $urlObject->setPath($urlParts['path']);
            }
        }
        // TODO: This has been used by former filter implementation, remove it:
        if (isset($urlParts['query'])) {
            $params = UrlParams::fromQueryString($urlParts['query'])->mergeValues($params);
        }

        if (isset($urlParts['fragment'])) {
            $urlObject->setAnchor($urlParts['fragment']);
        }

        $urlObject->setParams($params);
        return $urlObject;
    }

    /**
     * Overwrite the baseUrl
     *
     * If an empty Url is given '/' is used as the base
     *
     * @param   string  $baseUrl    The url path to use as the Url Base
     *
     * @return  $this
     */
    public function setBaseUrl($baseUrl)
    {
        if (($baseUrl = rtrim($baseUrl, '/ ')) ===  '') {
            $baseUrl = '/';
        }

        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Return the baseUrl set for this url
     *
     * @return  string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Set the relative path of this url, without query parameters
     *
     * @param   string  $path   The path to set
     *
     * @return  $this
     */
    public function setPath($path)
    {
        $this->path = ltrim($path, '/');
        return $this;
    }

    /**
     * Return the relative path of this url, without query parameters
     *
     * If you want the relative path with query parameters use getRelativeUrl
     *
     * @return  string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return the relative url with query parameters as a string
     *
     * @return  string
     */
    public function getRelativeUrl($separator = '&')
    {
        if ($this->params->isEmpty()) {
            return $this->path . $this->anchor;
        } else {
            return $this->path . '?' . $this->params->toString($separator) . $this->anchor;
        }
    }

    public function setQueryString($queryString)
    {
        $this->params = UrlParams::fromQueryString($queryString);
        return $this;
    }

    public function getQueryString()
    {
        return (string) $this->params;
    }

    /**
     * Return the absolute url with query parameters as a string
     *
     * @return  string
     */
    public function getAbsoluteUrl($separator = '&')
    {
        return $this->baseUrl . ($this->baseUrl !== '/' ? '/' : '') . $this->getRelativeUrl($separator);
    }

    /**
     * Add a set of parameters to the query part if the keys don't exist yet
     *
     * @param   array   $params     The parameters to add
     *
     * @return  $this
     */
    public function addParams(array $params)
    {
        foreach ($params as $k => $v) {
            $this->params->add($k, $v);
        }

        return $this;
    }

    /**
     * Set and overwrite the given params if one if the same key already exists
     *
     * @param   array   $params     The parameters to set
     *
     * @return  $this
     */
    public function overwriteParams(array $params)
    {
        foreach ($params as $k => $v) {
            $this->params->set($k, $v);
        }

        return $this;
    }

    /**
     * Overwrite the parameters used in the query part
     *
     * @param   UrlParams|array   $params     The new parameters to use for the query part
     *
     * @return  $this
     */
    public function setParams($params)
    {
        if ($params instanceof UrlParams) {
            $this->params = $params;
        } elseif (is_array($params)) {
            $urlParams = UrlParams::fromQueryString('');
            foreach ($params as $k => $v) {
                $urlParams->set($k, $v);
            }
            $this->params = $urlParams;
        } else {
            throw new ProgrammingError(
                'Url params needs to be either an array or an UrlParams instance'
            );
        }
        return $this;
    }

    /**
     * Return all parameters that will be used in the query part
     *
     * @return  UrlParams   An instance of UrlParam containing all parameters
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Return true if a urls' query parameter exists, otherwise false
     *
     * @param   string  $param    The url parameter name to check
     *
     * @return  bool
     */
    public function hasParam($param)
    {
        return $this->params->has($param);
    }

    /**
     * Return a url's query parameter if it exists, otherwise $default
     *
     * @param   string  $param      A query parameter name to return if existing
     * @param   mixed   $default    A value to return when the parameter doesn't exist
     *
     * @return  mixed
     */
    public function getParam($param, $default = null)
    {
        return $this->params->get($param, $default);
    }

    /**
     * Set a single parameter, overwriting any existing one with the same name
     *
     * @param   string          $param      The query parameter name
     * @param   array|string    $value      An array or string to set as the parameter value
     *
     * @return  $this
     */
    public function setParam($param, $value = true)
    {
        $this->params->set($param, $value);
        return $this;
    }

    /**
     * Set the url anchor-part
     *
     * @param   string  $anchor     The site's anchor string without the '#'
     *
     * @return  $this
     */
    public function setAnchor($anchor)
    {
        $this->anchor = '#' . $anchor;
        return $this;
    }

    /**
     * Remove provided key (if string) or keys (if array of string) from the query parameter array
     *
     * @param   string|array    $keyOrArrayOfKeys   An array of strings or a string representing the key(s)
     *                                              of the parameters to be removed
     * @return  $this
     */
    public function remove($keyOrArrayOfKeys)
    {
        $this->params->remove($keyOrArrayOfKeys);
        return $this;
    }

    /**
     * Shift a query parameter from this URL if it exists, otherwise $default
     *
     * @param string $param   Parameter name
     * @param mixed  $default Default value in case $param does not exist
     *
     * @return  mixed
     */
    public function shift($param, $default = null)
    {
        return $this->params->shift($param, $default);
    }

    /**
     * Whether the given URL matches this URL object
     *
     * This does an exact match, parameters MUST be in the same order
     *
     * @param Url|string $url the URL to compare against
     *
     * @return bool whether the URL matches
     */
    public function matches($url)
    {
        if (! $url instanceof Url) {
            $url = Url::fromPath($url);
        }
        return (string) $url === (string) $this;
    }

    /**
     * Return a copy of this url without the parameter given
     *
     * The argument can be either a single query parameter name or an array of parameter names to
     * remove from the query list
     *
     * @param   string|array    $keyOrArrayOfKeys   A single string or an array containing parameter names
     *
     * @return  Url
     */
    public function getUrlWithout($keyOrArrayOfKeys)
    {
        return $this->without($keyOrArrayOfKeys);
    }

    public function without($keyOrArrayOfKeys)
    {
        $url = clone($this);
        $url->remove($keyOrArrayOfKeys);
        return $url;
    }

    /**
     * Return a copy of this url with the given parameter(s)
     *
     * The argument can be either a single query parameter name or an array of parameter names to
     * remove from the query list
     *
     * @param string|array $param  A single string or an array containing parameter names
     * @param array        $values an optional values array
     *
     * @return Url
     */
    public function with($param, $values = null)
    {
        $url = clone($this);
        $url->params->mergeValues($param, $values);
        return $url;
    }

    public function __clone()
    {
        $this->params = clone $this->params;
    }

    /**
     * Alias for @see Url::getAbsoluteUrl()
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->getAbsoluteUrl('&amp;');
    }
}
