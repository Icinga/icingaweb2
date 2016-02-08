<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use ArrayIterator;
use IteratorAggregate;

/**
 * Maintain a set of cookies
 */
class CookieSet implements IteratorAggregate
{
    /**
     * Cookies in this set indexed by the cookie names
     *
     * @var Cookie[]
     */
    protected $cookies = array();

    /**
     * Get an iterator for traversing the cookies in this set
     *
     * @return  ArrayIterator   An iterator for traversing the cookies in this set
     */
    public function getIterator()
    {
        return new ArrayIterator($this->cookies);
    }

    /**
     * Add a cookie to the set
     *
     * If a cookie with the same name already exists, the cookie will be overridden.
     *
     * @param   Cookie  $cookie The cookie to add
     *
     * @return  $this
     */
    public function add(Cookie $cookie)
    {
        $this->cookies[$cookie->getName()] = $cookie;
        return $this;
    }

    /**
     * Get the cookie with the given name from the set
     *
     * @param   string  $name       The name of the cookie
     *
     * @return  Cookie|null         The cookie with the given name or null if the cookie does not exist
     */
    public function get($name)
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : null;
    }
}
