<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use InvalidArgumentException;

/**
 * A HTTP cookie
 */
class Cookie
{
    /**
     * Domain of the cookie
     *
     * @var string
     */
    protected $domain;

    /**
     * The timestamp at which the cookie expires
     *
     * @var int
     */
    protected $expire;

    /**
     * Whether to protect the cookie against client side script code attempts to read the cookie
     *
     * Defaults to true.
     *
     * @var bool
     */
    protected $httpOnly = true;

    /**
     * Name of the cookie
     *
     * @var string
     */
    protected $name;

    /**
     * The path on the web server where the cookie is available
     *
     * Defaults to the base URL.
     *
     * @var string
     */
    protected $path;

    /**
     * Whether to send the cookie only over a secure connection
     *
     * Defaults to auto-detection so that if the current request was sent over a secure connection the secure flag will
     * be set to true.
     *
     * @var bool
     */
    protected $secure;

    /**
     * Value of the cookie
     *
     * @var string
     */
    protected $value;

    /**
     * Create a new cookie
     *
     * @param   string  $name
     * @param   string  $value
     */
    public function __construct($name, $value = null)
    {
        if (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new InvalidArgumentException(sprintf(
                'Cookie name can\'t contain these characters: =,; \t\r\n\013\014 (%s)',
                $name
            ));
        }
        if (empty($name)) {
            throw new InvalidArgumentException('The cookie name can\'t be empty');
        }
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Get the domain of the cookie
     *
     * @return  string
     */
    public function getDomain()
    {
        if ($this->domain === null) {
            $this->domain = Config::app()->get('cookie', 'domain');
        }
        return $this->domain;
    }

    /**
     * Set the domain of the cookie
     *
     * @param   string  $domain
     *
     * @return  $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Get the timestamp at which the cookie expires
     *
     * @return  int
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * Set the timestamp at which the cookie expires
     *
     * @param   int $expire
     *
     * @return  $this
     */
    public function setExpire($expire)
    {
        $this->expire = $expire;
        return $this;
    }

    /**
     * Get whether to protect the cookie against client side script code attempts to read the cookie
     *
     * @return  bool
     */
    public function isHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     * Set whether to protect the cookie against client side script code attempts to read the cookie
     *
     * @param   bool    $httpOnly
     *
     * @return  $this
     */
    public function setHttpOnly($httpOnly)
    {
        $this->httpOnly = $httpOnly;
        return $this;
    }

    /**
     * Get the name of the cookie
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the path on the web server where the cookie is available
     *
     * If the path has not been set either via {@link setPath()} or via config, the base URL will be returned.
     *
     * @return  string
     */
    public function getPath()
    {
        if ($this->path === null) {
            $path = Config::app()->get('cookie', 'path');
            if ($path === null) {
                // The following call could be used as default for ConfigObject::get(), but we prevent unnecessary
                // function calls here, if the path is set in the config
                $path = Icinga::app()->getRequest()->getBaseUrl() . '/'; // Zend has rtrim($baseUrl, '/')
            }
            $this->path = $path;
        }
        return $this->path;
    }

    /**
     * Set the path on the web server where the cookie is available
     *
     * @param   string  $path
     *
     * @return  $this
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Get whether to send the cookie only over a secure connection
     *
     * If the secure flag has not been set either via {@link setSecure()} or via config and if the current request was
     * sent over a secure connection, true will be returned.
     *
     * @return bool
     */
    public function isSecure()
    {
        if ($this->secure === null) {
            $secure = Config::app()->get('cookie', 'secure');
            if ($secure === null) {
                // The following call could be used as default for ConfigObject::get(), but we prevent unnecessary
                // function calls here, if the secure flag is set in the config
                $secure = Icinga::app()->getRequest()->isSecure();
            }
            $this->secure = $secure;
        }
        return $this->secure;
    }

    /**
     * Set whether to send the cookie only over a secure connection
     *
     * @param   bool    $secure
     *
     * @return  $this
     */
    public function setSecure($secure)
    {
        $this->secure = $secure;
        return $this;
    }

    /**
     * Get the value of the cookie
     *
     * @return  string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of the cookie
     *
     * @param   string  $value
     *
     * @return  $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
