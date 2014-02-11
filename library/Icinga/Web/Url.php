<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

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
     * Rather dirty hack as the ApplicationBootstrap isn't an interface right now and can't be mocked
     * overwrite this to use a specific request for all Urls (so only in tests)
     *
     * @var null
     */
    public static $overwrittenRequest = null;

    /**
     * An array of all parameters stored in this Url
     *
     * @var array
     */
    private $params = array();

    /**
     * The site anchor after the '#'
     *
     * @var string
     */
    private $anchor = '';

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
            $request = self::getRequest();
        }

        $urlObject = new Url();
        $urlObject->setPath($request->getPathInfo());
        $urlObject->setParams(array_merge($request->getQuery(), $params));
        $urlObject->setBaseUrl($request->getBaseUrl());
        return $urlObject;
    }

    /**
     * Return a request object that should be used for determining the URL
     *
     * @return Zend_Abstract_Request
     */
    private static function getRequest()
    {
        if (self::$overwrittenRequest) {
            return self::$overwrittenRequest;
        }
        return Icinga::app()->getFrontController()->getRequest();
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
            $request = self::getRequest();
        }
        $urlObject->setBaseUrl($request->getBaseUrl());

        /*
         * Fetch fragment manually and remove it from the url, to 'help' the parse_url() function
         * parsing the url properly. Otherwise calling the function with a fragment, but without a
         * query will cause unpredictable behaviour.
         */
        $fragment = self::getUrlFragment($url);
        $url = self::stripUrlFragment($url);

        $urlParts = parse_url($url);

        if (isset($urlParts["path"])) {
            $urlObject->setPath($urlParts["path"]);
        }
        if (isset($urlParts["query"])) {
            $urlParams = array();
            parse_str($urlParts["query"], $urlParams);
            $params = array_merge($urlParams, $params);
        }
        if ($fragment !== '') {
            $urlObject->setAnchor($fragment);
        }

        $urlObject->setParams($params);
        return $urlObject;
    }

    /**
     * Get the fragment of a given url
     *
     * @param   $url    The url containing the fragment.
     *
     * @return  string  The fragment without the '#'
     */
    private static function getUrlFragment($url)
    {
        $url = parse_url($url);
        if (isset($url['fragment'])) {
            return $url['fragment'];
        } else {
            return '';
        }
    }

    /**
     * Remove the fragment-part of a given url
     *
     * @param $url  string  The url to strip from its fragment
     *
     * @return      string  The url without the fragment.
     */
    private static function stripUrlFragment($url)
    {
        return preg_replace('/#.*$/', '', $url);
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
     * Return the baseUrl set for this Url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
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
        if (empty($this->params)) {
            return ltrim($this->path, '/') . $this->anchor;
        }
        return ltrim($this->path, '/') . '?' . http_build_query($this->params) . $this->anchor;
    }

    /**
     * Return the absolute url with query parameters as a string
     *
     * @return string
     */
    public function getAbsoluteUrl()
    {
        $url = $this->getRelativeUrl();
        return preg_replace('/\/{2,}/', '/', '/'.$this->baseUrl.'/'.$url);
    }

    /**
     * Add a set of parameters to the query part if the keys don't exist yet
     *
     * @param array $params     The parameters to add
     * @return self
     */
    public function addParams(array $params)
    {
        $this->params += $params;
        return $this;
    }

    /**
     * Set and overwrite the given params if one if the same key already exists
     *
     * @param array $params     The parameters to set
     * @return self
     */
    public function overwriteParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
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
     * Set the url anchor-part
     *
     * @param   $anchor The site's anchor string without the '#'
     *
     * @return  self
     */
    public function setAnchor($anchor)
    {
        $this->anchor = '#' . $anchor;
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
    public function __toString()
    {
        return $this->getAbsoluteUrl();
    }
}
