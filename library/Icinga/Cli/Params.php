<?php

namespace Icinga\Cli;

class Params
{
    protected $program;
    protected $standalone = array();
    protected $params = array();

    public function __construct($argv)
    {
        $noOptionFlag = false;
        $this->program = array_shift($argv);
        for ($i = 0; $i < count($argv); $i++) {
            if ($argv[$i] === '--') {
                $noOptionFlag = true;
            } elseif (!$noOptionFlag && substr($argv[$i], 0, 2) === '--') {
                $key = substr($argv[$i], 2);
                if (! isset($argv[$i + 1]) || substr($argv[$i + 1], 0, 2) === '--') {
                    $this->params[$key] = true;
                } elseif (array_key_exists($key, $this->params)) {
                    if (!is_array($this->params[$key])) {
                        $this->params[$key] = array($this->params[$key]);
                    }
                    $this->params[$key][] = $argv[++$i];
                } else {
                    $this->params[$key] = $argv[++$i];
                }
            } else {
                $this->standalone[] = $argv[$i];
            }
        }
    }

    public function getStandalone($pos = 0, $default = null)
    {
        if (isset($this->standalone[$pos])) {
            return $this->standalone[$pos];
        }
        return $default;
    }

    public function count()
    {
        return count($this->standalone) + count($this->params);
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getAllStandalone()
    {
        return $this->standalone;
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function has($key)
    {
        return array_key_exists($key, $this->params);
    }

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->params[$key];
        }
        return $default;
    }

    public function set($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    public function remove($keys = array())
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

    public function without($keys = array())
    {
        $params = clone($this);
        return $params->remove($keys);
    }

    public function shift($key = null, $default = null)
    {
        if ($key === null) {
            if (count($this->standalone) > 0) {
                return array_shift($this->standalone);
            }
            return $default;
        }
        $result = $this->get($key, $default);
        if (is_array($result) && !is_array($default)) {
            $result = array_shift($result) || $default;
            if ($result === $default) {
                $this->remove($key);
            }
        } else {
            $this->remove($key);
        }
        return $result;
    }

    public function unshift($key)
    {
        array_unshift($this->standalone, $key);
        return $this;
    }

    public static function parse($argv = null)
    {
        if ($argv === null) {
            $argv = $GLOBALS['argv'];
        }
        $params = new self($argv);
        return $params;
    }
}
