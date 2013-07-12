<?php

namespace Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;

class Url
{
    protected $params = array();
    protected $path;
    protected $baseUrl;
    protected $request;

    public function __construct($url, $params = null, $request = null)
    {
        if ($request === null) {
            $this->request = Icinga::app()->frontController()->getRequest();
        } else {
            // Tests only
            $this->request = $request;
        }
        if ($url === null) {
            $this->path   = $this->request->getPathInfo();
            $this->params = $this->request->getQuery();
        } else {
            if (($split = strpos($url, '?')) === false) {
                $this->path = $url;
            } else {
                $this->path = substr($url, 0, $split);
                // TODO: Use something better than parse_str
                parse_str(substr($url, $split + 1), $urlParams);
                $this->params = $urlParams;
            }
        }
        if (! empty($params)) {
            $this->setParams($params);
        }
    }

    public static function create($url, $params = null, $request = null)
    {
        $u = new Url($url, $params, $request);
        return $u;
    }

    // For tests
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public static function current($request = null)
    {
        $url = new Url(null, null, $request);
        return $url;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getRelative()
    {
        $params = $args = array();
        foreach ($this->params as $name => $value) {
            if (is_int($name)) {
                $params[] = rawurlencode($value);
            } else {
                $args[] = rawurlencode($name) . '=' . rawurlencode($value);
            }
        }
        $url = vsprintf($this->path, $params);
        if (! empty($args)) {
            $url .= '?' . implode('&amp;', $args);
        }
        return $url;
    }

    public function addParams($params)
    {
        $this->params += $params;
        return $this;
    }

    public function setParams($params)
    {
        if ($params === null) {
            $this->params = array();
        } else {
            $this->params = $params;
        }
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function hasParam($key)
    {
        return array_key_exists($key, $this->params);
    }

    public function getParam($key, $default = null)
    {
        if ($this->hasParam($key)) {
            return $this->params[$key];
        }
        return $default;
    }

    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    public function remove()
    {
        $args = func_get_args();
        foreach ($args as $keys) {
            if (! is_array($keys)) {
                $keys = array($keys);
            }
            foreach ($keys as $key) {
                if (array_key_exists($key, $this->params)) {
                    unset($this->params[$key]);
                }
            }
        }
        return $this;
    }

    public function without()
    {
        $url = clone($this);
        $args = func_get_args();
        return call_user_func_array(array($url, 'remove'), $args);
    }

    public function __toString()
    {
        $url = $this->getRelative();
        $base = null === $this->baseUrl
            ? $this->request->getBaseUrl()
            : $this->baseUrl;
        if ($base === '' && $url[0]!== '/') {
            // Otherwise all URLs would be relative to wherever you are
            $base = '/';
        }
        if (strlen($base) > 0 && strlen($url) > 0 && $url[0] !== '?') {
            $base = rtrim($base, '/') . '/';
        }
        return $base . $url;
    }
}

