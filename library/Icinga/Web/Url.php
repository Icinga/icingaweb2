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
use Icinga\Exception\ProgrammingError;

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
     * @var array
     */
    protected $params = array();

    /**
     * An array to map aliases to valid parameters
     *
     * @var array
     */
    protected $aliases = array();

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

    }

    /**
     * Create a new Url class representing the current request
     *
     * If $params are given, those will be added to the request's parameters
     * and overwrite any existing parameters
     *
     * @param   array           $params     Parameters that should additionally be considered for the url
     * @param   Zend_Request    $request    A request to use instead of the default one
     *
     * @return  Url
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
     * @return  Zend_Abstract_Request
     */
    protected static function getRequest()
    {
        return Icinga::app()->getFrontController()->getRequest();
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
            throw new ProgrammingError(sprintf('url "%s" is not a string', $url));
        }

        $urlObject = new Url();
        $baseUrl = $request->getBaseUrl();
        $urlObject->setBaseUrl($baseUrl);

        // Fetch fragment manually and remove it from the url, to 'help' the parse_url() function
        // parsing the url properly. Otherwise calling the function with a fragment, but without a
        // query will cause unpredictable behaviour.
        $url = self::stripUrlFragment($url);
        $urlParts = parse_url($url);
        if (isset($urlParts['path'])) {
            if ($baseUrl !== '' && strpos($urlParts['path'], $baseUrl) === 0) {
                $urlObject->setPath(substr($urlParts['path'], strlen($baseUrl)));
            } else {
                $urlObject->setPath($urlParts['path']);
            }
        }
        if (isset($urlParts['query'])) {
            $urlParams = array();
            parse_str($urlParts['query'], $urlParams);
            $params = array_merge($urlParams, $params);
        }

        $fragment = self::getUrlFragment($url);
        if ($fragment !== '') {
            $urlObject->setAnchor($fragment);
        }

        $urlObject->setParams($params);
        return $urlObject;
    }

    /**
     * Get the fragment of a given url
     *
     * @param   string  $url    The url containing the fragment.
     *
     * @return  string          The fragment without the '#'
     */
    protected static function getUrlFragment($url)
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
     * @param   string  $url    The url to strip from its fragment
     *
     * @return  string          The url without the fragment
     */
    protected static function stripUrlFragment($url)
    {
        return preg_replace('/#.*$/', '', $url);
    }

    /**
     * Set the array to be used to map aliases to valid parameters
     *
     * @param   array   $aliases    The array to be used (alias => param)
     *
     * @return  self
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     * Return the parameter for the given alias
     *
     * @param   string  $alias  The alias to translate
     *
     * @return  string          The parameter name
     */
    public function translateAlias($alias)
    {
        return array_key_exists($alias, $this->aliases) ? $this->aliases[$alias] : $alias;
    }

    /**
     * Overwrite the baseUrl
     *
     * If an empty Url is given '/' is used as the base
     *
     * @param   string  $baseUrl    The url path to use as the Url Base
     *
     * @return  self
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
     * @return  self
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
    public function getRelativeUrl()
    {
        if (empty($this->params)) {
            return $this->path . $this->anchor;
        }

        $params = array();
        foreach ($this->params as $param => $value) {
            $params[$this->translateAlias($param)] = $value;
        }

        return $this->path . '?' . http_build_query($params, '', '&amp;') . $this->anchor;
    }

    /**
     * Return the absolute url with query parameters as a string
     *
     * @return  string
     */
    public function getAbsoluteUrl()
    {
        return $this->baseUrl . ($this->baseUrl !== '/' ? '/' : '') . $this->getRelativeUrl();
    }

    /**
     * Add a set of parameters to the query part if the keys don't exist yet
     *
     * @param   array   $params     The parameters to add
     *
     * @return  self
     */
    public function addParams(array $params)
    {
        $this->params += $params;
        return $this;
    }

    /**
     * Set and overwrite the given params if one if the same key already exists
     *
     * @param   array   $params     The parameters to set
     *
     * @return  self
     */
    public function overwriteParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * Overwrite the parameters used in the query part
     *
     * @param   array   $params     The new parameters to use for the query part
     *
     * @return  self
     */
    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Return all parameters that will be used in the query part
     *
     * @return  array   An associative key => value array containing all parameters
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
        return array_key_exists($param, $this->params);
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
        if ($this->hasParam($param)) {
            return $this->params[$param];
        }

        return $default;
    }

    /**
     * Set a single parameter, overwriting any existing one with the same name
     *
     * @param   string          $param      The query parameter name
     * @param   array|string    $value      An array or string to set as the parameter value
     *
     * @return  self
     */
    public function setParam($param, $value)
    {
        $this->params[$param] = $value;
        return $this;
    }

    /**
     * Set the url anchor-part
     *
     * @param   string  $anchor     The site's anchor string without the '#'
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
     * @param   string|array    $keyOrArrayOfKeys   An array of strings or a string representing the key(s)
     *                                              of the parameters to be removed
     * @return  self
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
     * @param   array   $keys   An array of strings containing parameter names to remove
     *
     * @return  self
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
     * @param   string  $key    The key to remove from the url
     *
     * @return  self
     */
    public function removeKey($key)
    {
        if (isset($this->params[$key])) {
            unset($this->params[$key]);
        }

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
        if (isset($this->params[$param])) {
            $ret = $this->params[$param];
            unset($this->params[$param]);
        } else {
            $ret = $default;
        }
        return $ret;
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
        $url = clone($this);
        $url->remove($keyOrArrayOfKeys);
        return $url;
    }

    /**
     * Alias for @see Url::getAbsoluteUrl()
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->getAbsoluteUrl();
    }
}
