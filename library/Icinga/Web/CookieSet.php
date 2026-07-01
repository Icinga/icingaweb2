<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

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
    protected $cookies = [];

    /**
     * Get an iterator for traversing the cookies in this set
     *
     * @return  ArrayIterator   An iterator for traversing the cookies in this set
     */
    public function getIterator(): Traversable
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
