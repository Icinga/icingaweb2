<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;
use Icinga\Data\Filter\Filter;

/**
 * Url class that provides convenient access to parameters, allows to modify query parameters and
 * returns Urls reflecting all changes made to the url and to the parameters.
 *
 * Direct instantiation is prohibited and should be done either with @see Url::fromRequest() or
 * @see Url::fromPath()
 */
class Url
{
    /**
     * Whether this url points to an external resource
     *
     * @var bool
     */
    protected $external;

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
     * The basePath of this Url
     *
     * @var string
     */
    protected $basePath;

    /**
     * The host of this Url
     *
     * @var string
     */
    protected $host;

    /**
     * The port of this Url
     *
     * @var string
     */
    protected $port;

    /**
     * The scheme of this Url
     *
     * @var string
     */
    protected $scheme;

    /**
     * The username passed with this Url
     *
     * @var string
     */
    protected $username;

    /**
     * The password passed with this Url
     *
     * @var string
     */
    protected $password;

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
            $request = static::getRequest();
        }

        $url = new static();
        $url->setPath(ltrim($request->getPathInfo(), '/'));

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
        $url->setBasePath($request->getBaseUrl());
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
            throw new ProgrammingError(
                'Url::fromRequest and Url::fromPath are currently not supported for CLI operations'
            );
        } else {
            return $app->getRequest();
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
            $request = static::getRequest();
        }

        if (! is_string($url)) {
            throw new ProgrammingError(
                'url "%s" is not a string',
                $url
            );
        }

        $urlObject = new static();

        if ($url === '#') {
            $urlObject->setPath($url);
            return $urlObject;
        }

        $urlParts = parse_url($url);
        if (isset($urlParts['scheme']) && (
            $urlParts['scheme'] !== $request->getScheme()
            || (isset($urlParts['host']) && $urlParts['host'] !== $request->getServer('SERVER_NAME'))
            || (isset($urlParts['port']) && $urlParts['port'] != $request->getServer('SERVER_PORT')))
        ) {
            $urlObject->setIsExternal();
        }

        if (isset($urlParts['path'])) {
            $urlPath = $urlParts['path'];
            if ($urlPath && $urlPath[0] === '/') {
                if ($urlObject->isExternal() || isset($urlParts['user'])) {
                    $urlPath = substr($urlPath, 1);
                } else {
                    $requestBaseUrl = $request->getBaseUrl();
                    if ($requestBaseUrl && $requestBaseUrl !== '/' && strpos($urlPath, $requestBaseUrl) === 0) {
                        $urlPath = substr($urlPath, strlen($requestBaseUrl) + 1);
                        $urlObject->setBasePath($requestBaseUrl);
                    }
                }
            } elseif (! $urlObject->isExternal()) {
                $urlObject->setBasePath($request->getBaseUrl());
            }

            $urlObject->setPath($urlPath);
        } elseif (! $urlObject->isExternal()) {
            $urlObject->setBasePath($request->getBaseUrl());
        }

        // TODO: This has been used by former filter implementation, remove it:
        if (isset($urlParts['query'])) {
            $params = UrlParams::fromQueryString($urlParts['query'])->mergeValues($params);
        }
        if (isset($urlParts['fragment'])) {
            $urlObject->setAnchor($urlParts['fragment']);
        }

        if (isset($urlParts['user']) || $urlObject->isExternal()) {
            if (isset($urlParts['user'])) {
                $urlObject->setUsername($urlParts['user']);
            }
            if (isset($urlParts['host'])) {
                $urlObject->setHost($urlParts['host']);
            }
            if (isset($urlParts['port'])) {
                $urlObject->setPort($urlParts['port']);
            }
            if (isset($urlParts['scheme'])) {
                $urlObject->setScheme($urlParts['scheme']);
            }
            if (isset($urlParts['pass'])) {
                $urlObject->setPassword($urlParts['pass']);
            }
        }

        $urlObject->setParams($params);
        return $urlObject;
    }

    /**
     * Create a new filter that needs to fullfill the base filter and the optional filter (if it exists)
     *
     * @param string    $url        The url to apply the new filter to
     * @param Filter    $filter     The base filter
     * @param Filter    $optional   The optional filter
     *
     * @return Url                  The altered URL containing the new filter
     * @throws ProgrammingError
     */
    public static function urlAddFilterOptional($url, $filter, $optional)
    {
        $url = static::fromPath($url);
        $f = $filter;
        if (isset($optional)) {
            $f = Filter::matchAll($filter, $optional);
        }
        return $url->setQueryString($f->toQueryString());
    }

    /**
     * Add the given filter to the current filter of the URL
     *
     * @param   Filter $and
     *
     * @return  $this
     */
    public function addFilter($and)
    {
        $this->setQueryString(
            Filter::fromQueryString($this->getQueryString())
                ->andFilter($and)
                ->toQueryString()
        );
        return $this;
    }

    /**
     * Set the basePath for this url
     *
     * @param   string  $basePath     New basePath of this url
     *
     * @return  $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '/ ');
        return $this;
    }

    /**
     * Return the basePath set for this url
     *
     * @return  string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Set the host for this url
     *
     * @param   string  $host     New host of this Url
     *
     * @return  $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Return the host set for this url
     *
     * @return  string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the port for this url
     *
     * @param   string  $port    New port of this url
     *
     * @return  $this
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Return the port set for this url
     *
     * @return  string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the scheme for this url
     *
     * @param   string  $scheme    The scheme used for this url
     *
     * @return  $this
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Return the scheme set for this url
     *
     * @return  string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set the baseUrl for this url
     *
     * @deprecated  Please create a new url from scratch instead
     *
     * @param       string  $baseUrl    The url path to use as the url base
     *
     * @return      $this
     */
    public function setBaseUrl($baseUrl)
    {
        $urlParts = parse_url($baseUrl);
        if (isset($urlParts["host"])) {
            $this->setHost($urlParts["host"]);
        }
        if (isset($urlParts["port"])) {
            $this->setPort($urlParts["port"]);
        }
        if (isset($urlParts['scheme'])) {
            $this->setScheme($urlParts['scheme']);
        }
        if (isset($urlParts['user'])) {
            $this->setUsername($urlParts['user']);
        }
        if (isset($urlParts['pass'])) {
            $this->setPassword($urlParts['pass']);
        }
        if (isset($urlParts['path'])) {
            $this->setBasePath($urlParts['path']);
        }

        return $this;
    }

    /**
     * Return the baseUrl for this url
     *
     * @deprecated
     *
     * @return  string
     */
    public function getBaseUrl()
    {
        if (! $this->isExternal()) {
            return $this->getBasePath();
        }

        $urlString = '';
        if ($this->getScheme()) {
            $urlString .= $this->getScheme() . '://';
        }
        if ($this->getPassword()) {
            $urlString .= $this->getUsername() . ':' . $this->getPassword() . '@';
        } elseif ($this->getUsername()) {
            $urlString .= $this->getUsername() . '@';
        }
        if ($this->getHost()) {
            $urlString .= $this->getHost();
        }
        if ($this->getPort()) {
            $urlString .= ':' . $this->getPort();
        }
        if ($this->getBasePath()) {
            $urlString .= $this->getBasePath();
        }

        return $urlString;
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
        $this->path = $path;
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
     * Set whether this url points to an external resource
     *
     * @param   bool    $state
     *
     * @return  $this
     */
    public function setIsExternal($state = true)
    {
        $this->external = (bool) $state;
        return $this;
    }

    /**
     * Return whether this url points to an external resource
     *
     * @return  bool
     */
    public function isExternal()
    {
        return $this->external;
    }

    /**
     * Set the username passed with this url
     *
     * @param   string  $username   The username to set
     *
     * @return  $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Return the username passed with this url
     *
     * @return  string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the username passed with this url
     *
     * @param   string  $password   The password to set
     *
     * @return  $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Return the password passed with this url
     *
     * @return  string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Return the relative url
     *
     * @return  string
     */
    public function getRelativeUrl($separator = '&')
    {
        $path = $this->buildPathQueryAndFragment($separator);
        if ($path && $path[0] === '/') {
            return '';
        }

        return $path;
    }

    /**
     * Return this url's path with its query parameters and fragment as string
     *
     * @return  string
     */
    protected function buildPathQueryAndFragment($querySeparator)
    {
        $anchor = $this->getAnchor();
        if ($anchor) {
            $anchor = '#' . $anchor;
        }

        $query = $this->getQueryString($querySeparator);
        if ($query) {
            $query = '?' . $query;
        }

        return $this->getPath() . $query . $anchor;
    }

    public function setQueryString($queryString)
    {
        $this->params = UrlParams::fromQueryString($queryString);
        return $this;
    }

    public function getQueryString($separator = null)
    {
        return $this->params->toString($separator);
    }

    /**
     * Return the absolute url with query parameters as a string
     *
     * @return  string
     */
    public function getAbsoluteUrl($separator = '&')
    {
        $path = $this->buildPathQueryAndFragment($separator);
        if ($path && ($path === '#' || $path[0] === '/')) {
            return $path;
        }

        $basePath = $this->getBasePath();
        if (! $basePath) {
            $basePath = '/';
        }

        if ($this->getUsername() || $this->isExternal()) {
            $urlString = '';
            if ($this->getScheme()) {
                $urlString .= $this->getScheme() . '://';
            }
            if ($this->getPassword()) {
                $urlString .= $this->getUsername() . ':' . $this->getPassword() . '@';
            } elseif ($this->getUsername()) {
                $urlString .= $this->getUsername() . '@';
            }
            if ($this->getHost()) {
                $urlString .= $this->getHost();
            }
            if ($this->getPort()) {
                $urlString .= ':' . $this->getPort();
            }

            return $urlString . $basePath . ($basePath !== '/' && $path ? '/' : '') . $path;
        } else {
            return $basePath . ($basePath !== '/' && $path ? '/' : '') . $path;
        }
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
        $this->anchor = $anchor;
        return $this;
    }

    /**
     * Return the url anchor-part
     *
     * @return  string  The site's anchor string without the '#'
     */
    public function getAnchor()
    {
        return $this->anchor;
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
        if (! $url instanceof static) {
            $url = static::fromPath($url);
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
