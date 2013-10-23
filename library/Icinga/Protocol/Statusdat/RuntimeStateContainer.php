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

namespace Icinga\Protocol\Statusdat;

/**
 * Container class containing the runtime state of an object
 *
 * This class contains the state of the object as a string and parses it
 * on the fly as soon as values should be retrieved. This reduces memory usage,
 * as most runtime information is never received and only lives for a very short time.
 *
 */
class RuntimeStateContainer extends \stdClass
{
    /**
     * The state string
     *
     * @var string
     */
    public $runtimeState = "";

    /**
     * Create a new runtime state container from the givven string
     *
     * @param string $str
     */
    public function __construct($str = "")
    {
        $this->runtimeState = $str;
    }

    /**
     * Return true if the argument exists
     *
     * @param  String $attr The argument to retrieve
     * @return bool         True if it exists, otherwise false
     */
    public function __isset($attr)
    {
        try {
            $this->__get($attr);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Return the given attribute
     *
     * If the container string is not yet parsed, this will happen here
     *
     * @param  String $attr                 The attribute to retrieve
     * @return mixed                        The value of the attribute
     * @throws \InvalidArgumentException    When the attribute does not exist
     */
    public function __get($attr)
    {
        $start = strpos($this->runtimeState, $attr . "=");
        if ($start === false) {
            throw new \InvalidArgumentException("Unknown property $attr");
        }

        $start += strlen($attr . "=");
        $len = strpos($this->runtimeState, "\n", $start) - $start;
        $this->$attr = trim(substr($this->runtimeState, $start, $len));

        return $this->$attr;
    }

}
