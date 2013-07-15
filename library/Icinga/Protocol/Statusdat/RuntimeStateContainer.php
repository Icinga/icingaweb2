<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 * 
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat;

/**
 * Class RuntimeStateContainer
 * @package Icinga\Protocol\Statusdat
 */
class RuntimeStateContainer extends \stdClass
{
    /**
     * @var string
     */
    public $runtimeState = "";

    /**
     * @param string $str
     */
    public function __construct($str = "")
    {
        $this->runtimeState = $str;
    }

    /**
     * @param $attr
     * @return bool
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
     * @param $attr
     * @return mixed
     * @throws \InvalidArgumentException
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
