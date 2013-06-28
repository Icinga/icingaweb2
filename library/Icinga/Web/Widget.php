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

namespace Icinga\Web;

use Icinga\Exception\ProgrammingError;

/**
 * Web widgets make things easier for you!
 *
 * This class provides nothing but a static factory method for widget creation.
 * Usually it will not be used directly as there are widget()-helpers available
 * in your action controllers and view scripts.
 *
 * Usage example:
 * <code>
 * $tabs = Widget::create('tabs');
 * </code>
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Widget
{
    /**
     * Create a new widget
     *
     * @param string $name    Widget name
     * @param array $options Widget constructor options
     *
     * @throws \Icinga\Exception\ProgrammingError
     * @return Icinga\Web\Widget\AbstractWidget
     */
    public static function create($name, $options = array())
    {
        $class = 'Icinga\\Web\\Widget\\' . ucfirst($name);

        if (! class_exists($class)) {
            throw new ProgrammingError(
                sprintf(
                    'There is no such widget: %s',
                    $name
                )
            );
        }

        $widget = new $class($options);
        return $widget;
    }
}
