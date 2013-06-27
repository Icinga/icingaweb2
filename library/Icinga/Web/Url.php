<?php

namespace Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;

class Url
{
    protected $params = array();
    protected $url;
    protected $baseUrl;

    public function __construct($url, $params = null)
    {
        if (($split = strpos($url, '?')) === false) {
            $this->url = $url;
            if (! empty($params)) {
                $this->params = $params;
            }
        } else {
            $this->url = substr($url, 0, $split);
            parse_str(substr($url, $split + 1), $urlParams);
            $this->params = $urlParams;
            if (! empty($params)) {
                $this->params += $params;
                // TODO: Test += behavior!
            }
        }

    }

    public static function create($url, $params = null)
    {
        $u = new Url($url, $params);
        return $u;
    }

    // For tests
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public static function current()
    {
        $app = Icinga::app();
        $view = $app->getView()->view;
        $request = $app->frontController()->getRequest();
        $parts = array();
        // TODO: getQuery!
        $params = $request->getParams();
        foreach (array('module', 'controller', 'action') as $param) {
            if ($view->{$param . '_name'} !== 'default') {
                $parts[] = $view->{$param . '_name'};
            }
            if (array_key_exists($param, $params)) {
                unset($params[$param]);
            }
        }
        $rel = implode('/', $parts);
        $url = new Url($rel, $params);
        return $url;
    }

    public function getScript()
    {
        return $this->url;
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
        $url = vsprintf($this->url, $params);
        if (! empty($args)) {
            $url .= '?' . implode('&', $args);
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
        $this->params = $params;
        return $this;
    }

    public function set($key, $val)
    {
        $this->params[$key] = $val;
        return $this;
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

    public function getParams()
    {
        return $this->params;
    }

    public function without($keys)
    {
        if (! is_array($keys)) {
            $keys = array($keys);
        }
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->params)) {
                unset($this->params[$key]);
            }
        }
        return $this;
    }

    public function __toString()
    {
        $url = $this->getRelative();
        $base = is_null($this->baseUrl)
            ? Icinga::app()->getView()->view->baseUrl()
            : $this->baseUrl;
        if ($base === '') {
            // Otherwise all URLs would be relative to wherever you are
            $base = '/';
        }
        if (strlen($base) > 0 && strlen($url) > 0 && $url[0] !== '?') {
            $base = rtrim($base, '/') . '/';
        }
        return $base . $url;
    }
}
