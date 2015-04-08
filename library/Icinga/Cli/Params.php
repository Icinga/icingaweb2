<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Cli;

use Icinga\Exception\MissingParameterException;

/**
 * Params
 *
 * A class to ease commandline-option and -argument handling.
 */
class Params
{
    /**
     * The name and path of the executable
     *
     * @var string
     */
    protected $program;

    /**
     * The arguments
     *
     * @var array
     */
    protected $standalone = array();

    /**
     * The options
     *
     * @var array
     */
    protected $params = array();

    /**
     * Parse the given commandline and create a new Params object
     *
     * @param   array   $argv   The commandline
     */
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

    /**
     * Return the value for an argument by position
     *
     * @param   int     $pos        The position of the argument
     * @param   mixed   $default    The default value to return
     *
     * @return  mixed
     */
    public function getStandalone($pos = 0, $default = null)
    {
        if (isset($this->standalone[$pos])) {
            return $this->standalone[$pos];
        }
        return $default;
    }

    /**
     * Count and return the number of arguments and options
     *
     * @return int
     */
    public function count()
    {
        return count($this->standalone) + count($this->params);
    }

    /**
     * Return the options
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Return the arguments
     *
     * @return array
     */
    public function getAllStandalone()
    {
        return $this->standalone;
    }

    /**
     * Support isset() and empty() checks on options
     *
     * @param   $name
     *
     * @return  bool
     */
    public function __isset($name)
    {
        return isset($this->params[$name]);
    }

    /**
     * @see Params::get()
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Return whether the given option exists
     *
     * @param   string  $key    The option name to check
     *
     * @return  bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * Return the value of the given option
     *
     * @param   string  $key        The option name
     * @param   mixed   $default    The default value to return
     *
     * @return  mixed
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->params[$key];
        }
        return $default;
    }

    /**
     * Require a parameter
     *
     * @param   string  $name               Name of the parameter
     * @param   bool    $strict             Whether the parameter's value must not be the empty string
     *
     * @return  mixed
     *
     * @throws  MissingParameterException   If the parameter was not given
     */
    public function req($name, $strict = true)
    {
        if ($this->has($name)) {
            $value = $this->get($name);
            if (! $strict || strlen($value) > 0) {
                return $value;
            }
        }
        $e = new MissingParameterException(t('Required parameter \'%s\' missing'), $name);
        $e->setParameter($name);
        throw $e;
    }

    /**
     * Set a value for the given option
     *
     * @param   string  $key    The option name
     * @param   mixed   $value  The value to set
     *
     * @return  $this
     */
    public function set($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Remove a single option or multiple options
     *
     * @param   string|array    $keys   The option or options to remove
     *
     * @return  $this
     */
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

    /**
     * Return a copy of this object with the given options being removed
     *
     * @param   string|array    $keys   The option or options to remove
     *
     * @return  Params
     */
    public function without($keys = array())
    {
        $params = clone($this);
        return $params->remove($keys);
    }

    /**
     * Remove and return the value of the given option
     *
     * Called multiple times for an option with multiple values returns
     * them one by one in case the default is not an array.
     *
     * @param   string  $key        The option name
     * @param   mixed   $default    The default value to return
     *
     * @return  mixed
     */
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

    /**
     * Put the given value onto the argument stack
     *
     * @param   mixed   $key    The argument
     *
     * @return  $this
     */
    public function unshift($key)
    {
        array_unshift($this->standalone, $key);
        return $this;
    }

    /**
     * Parse the given commandline
     *
     * @param   array   $argv   The commandline to parse
     *
     * @return  Params
     */
    public static function parse($argv = null)
    {
        if ($argv === null) {
            $argv = $GLOBALS['argv'];
        }
        $params = new self($argv);
        return $params;
    }
}
