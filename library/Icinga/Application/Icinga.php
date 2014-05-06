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

namespace Icinga\Application;

use Icinga\Exception\ProgrammingError;

/**
 * Icinga application container
 */
class Icinga
{
    /**
     * @var ApplicationBootstrap
     */
    private static $app;

    /**
     * Getter for an application environment
     *
     * @return ApplicationBootstrap|Web
     * @throws ProgrammingError
     */
    public static function app()
    {
        if (self::$app == null) {
            throw new ProgrammingError('Icinga has never been started');
        }

        return self::$app;
    }

    /**
     * Setter for an application environment
     *
     * @param   ApplicationBootstrap    $app
     * @param   bool                    $overwrite
     *
     * @throws ProgrammingError
     */
    public static function setApp(ApplicationBootstrap $app, $overwrite = false)
    {
        if (self::$app !== null && !$overwrite) {
            throw new ProgrammingError('Cannot start Icinga twice');
        }

        self::$app = $app;
    }
}
