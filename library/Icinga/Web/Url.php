<?php

namespace Icinga\Web;

use Icinga\Application\Icinga;

/**
 *  Url class that provides convenient access to parameters, allows to modify query parameters and
 *  returns Urls reflecting all changes made to the url and to the parameters.
 *
 *  Direct instantiation is prohibited and should be done either with @see Url::fromRequest() or
 *  @see Url::fromUrlString()
 *
 *  Currently, protocol, host and port are ignored and will be implemented when required
 *
 */
class Url
{
    /**
     * An array of all parameters stored in this Url
     *
     * @var array
     */
    private $params = array();

    /**
     * The relative path of this Url, without query parameters
     *
     * @var string
     */
    private $path = '';

    /**
     * The baseUrl that will be appended to @see Url::$path in order to
     * create an absolute Url
     *
     * @var string
     */
    private $baseUrl = '/';



    private function __construct()
    {
    }

    /**
     * Create a new Url class representing the current request
     *
     * If $params are given, those will be added to the request's parameters
     * and overwrite any existing parameters
     *
     * @param string $url           The string representation of the Url to parse
     * @param array $params         Parameters that should additionally be considered for the Url
     * @param Zend_Request $request A request to use instead of the default one
     *
     * @return Url
     */
    public static function fromRequest(array $params = array(), $request = null)
    {
        if ($request === null) {
            $request = Icinga::app()->getFrontController()->getRequest();
        }

        $urlObject = new Url();
        $urlObject->setPath($request->getPathInfo());
        $urlObject->setParams(array_merge($request->getQuery(), $params));
        $urlObject->setBaseUrl($request->getBaseUrl());
        return $urlObject;
    }

    /**
     * Create a new Url class representing the given url
     *
     * If $params are given, those will be added to the urls parameters
     * and overwrite any existing parameters
     *
     * @param string $url               The string representation of the Url to parse
     * @param array $params             An array of parameters that should additionally be considered for the Url
     * @param Zend_Request $request A   request to use instead of the default one
     *
     * @return Url
     */
    public static function fromPath($url, array $params = array(), $request = null)
    {
        $urlObject = new Url();
        if ($request === null) {
            $request = Icinga::app()->getFrontController()->getRequest();
        }
        $urlObject->setBaseUrl($request->getBaseUrl());

        $urlParts = parse_url($url);
        if (isset($urlParts["path"])) {
            $urlObject->setPath($urlParts["path"]);
        }
        if (isset($urlParts["query"])) {
            parse_str($urlParts["query"], $urlParams);
            $params = array_merge($urlParams, $params);
        }

        $urlObject->setParams($params);
        return $urlObject;
    }


    /**
     * Overwrite the baseUrl.
     *
     * If an empty Url is given '/' is used as the base
     *
     * @param string $baseUrl      The url path to use as the Url Base
     * @return $this
     */
    public function setBaseUrl($baseUrl)
    {
        if (trim($baseUrl) ==  '') {
            $baseUrl = '/';
        }
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Set the relative path of this url, without query parameters
     *
     * @param string $path         The path to set
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Return the relative path of this Url, without query parameters
     *
     * If you want the relative path with query parameters use getRelativeUrl
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return the relative url with query parameters as a string
     *
     * @return string
     */
    public function getRelativeUrl()
    {
        if (empty($this->params))
            return ltrim($this->path,'/');
        return ltrim($this->path,'/').'?'.http_build_query($this->params);
    }

    /**
     * Return the absolute url with query parameters as a string
     *
     * @return string
     */
    public function getAbsoluteUrl()
    {
        $url = $this->getRelativeUrl();
        $baseUrl = '/'.ltrim($this->baseUrl, '/');
        return $baseUrl.'/'.$url;
    }

    /**
     * Add a set of parameters to the query part
     *
     * @param array $params  The parameters to add
     * @return $this
     */
    public function addParams(array $params)
    {
        $this->params += $params;
        return $this;
    }

    /**
     * Overwrite the parameters used in the query part
     *
     * @param array $params     The new parameters to use for the query part
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Return all parameters that will be used in the query part
     *
     * @return array        An associative key => value array containing all parameters
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Return true if the Urls' query parameter with $key exists, otherwise false
     *
     * @param $key      A key to check for existing
     * @return bool
     */
    public function hasParam($key)
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * Return the Url's query parameter with the name $key if exists, otherwise $default
     *
     * @param $key              A query parameter name to return if existing
     * @param mixed $default    A value to return when the parameter doesn't exist
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        if ($this->hasParam($key)) {
            return $this->params[$key];
        }
        return $default;
    }

    /**
     * Set a single parameter $key, overwriting existing ones with the same key
     *
     * @param string $key           A string representing the key of the parameter
     * @param array|string $value   An array or string to set as the parameter value
     * @return $this
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Remove provided key (if string) or keys (if array of string) from the query parameter array
     *
     * @param string|array  $keyOrArrayOfKeys       An array of strings or a string representing the key(s)
     *                                              of the parameters to be removed
     * @return $this
     */
    public function remove($keyOrArrayOfKeys)
    {
        if (is_array($keyOrArrayOfKeys)) {
            $this->removeKeys($keyOrArrayOfKeys);
        } else {
            $this->removeKey($keyOrArrayOfKeys);
        }
        return $this;
    }

    /**
     * Remove all parameters with the parameter names in the $keys array
     *
     * @param array $keys   An array of strings containing parameter names to remove
     * @return $this
     */
    public function removeKeys(array $keys)
    {
        foreach ($keys as $key) {
            $this->removeKey($key);
        }
        return $this;
    }

    /**
     * Remove a single parameter with the provided parameter name $key
     *
     * @param string $key   The key to remove from the Url
     * @return $this
     */
    public function removeKey($key)
    {
        if (isset($this->params[$key])) {
            unset($this->params[$key]);
        }
        return $this;
    }

    /**
     * Return a copy of this url without the parameter given
     *
     * The argument can either a single query parameter name or an array of parameter names to
     * remove from the query list
     *
     * @param string|array $keyOrArrayOfKeys    A single string or an array containing parameter names
     * @return Url
     */
    public function getUrlWithout($keyOrArrayOfKeys)
    {
        $url = clone($this);
        $url->remove($keyOrArrayOfKeys);
        return $url;
    }

    /**
     * Alias for @see Url::getAbsoluteUrl()
     * @return mixed
     */
    public function __toString() {
        return $this->getAbsoluteUrl();
    }
}
