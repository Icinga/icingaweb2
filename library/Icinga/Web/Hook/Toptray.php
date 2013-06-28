<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Hook;

use Icinga\Application\Logger as Logger;

/**
 * Class Toptray
 * @package Icinga\Web\Hook
 */
abstract class Toptray
{
    /**
     *
     */
    const ALIGN_LEFT = "pull-left";

    /**
     *
     */
    const ALIGN_NONE = "";

    /**
     *
     */
    const ALIGN_RIGHT = "pull-right";

    /**
     * @var string
     */
    protected $align = self::ALIGN_NONE;

    /**
     * @param $align
     */
    public function setAlignment($align)
    {
        $this->align = $align;
    }

    /**
     * @return string
     */
    final public function getWidgetDOM()
    {
        try {
            return '<ul class="nav ' . $this->align . '" >' . $this->buildDOM() . '</ul>';
        } catch (\Exception $e) {
            Logger::error("Could not create tray widget : %s", $e->getMessage());
            return '';
        }


    }

    /**
     * @return mixed
     */
    abstract protected function buildDOM();
}
