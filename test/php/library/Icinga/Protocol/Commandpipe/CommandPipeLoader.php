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

namespace Tests\Icinga\Protocol\Commandpipe;

require_once("./library/Icinga/LibraryLoader.php");

use Test\Icinga\LibraryLoader;

class CommandPipeLoader extends LibraryLoader {

    public static function requireLibrary()
    {
        require_once("Zend/Config.php");
        require_once("Zend/Log.php");
        require_once("../../library/Icinga/Application/Logger.php");

        require_once("../../library/Icinga/Protocol/Commandpipe/Comment.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/CommandType.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/CommandPipe.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/PropertyModifier.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Exception/InvalidCommandException.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Transport/Transport.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Transport/SecureShell.php");
        require_once("../../library/Icinga/Protocol/Commandpipe/Transport/LocalPipe.php");
        require_once('../../modules/monitoring/library/Monitoring/Command/BaseCommand.php');
        require_once('../../modules/monitoring/library/Monitoring/Command/AcknowledgeCommand.php');
        require_once('../../modules/monitoring/library/Monitoring/Command/AddCommentCommand.php');
        require_once('../../modules/monitoring/library/Monitoring/Command/ScheduleDowntimeCommand.php');
        require_once('../../modules/monitoring/library/Monitoring/Command/CustomNotificationCommand.php');
        require_once('../../modules/monitoring/library/Monitoring/Command/DelayNotificationCommand.php');
    }
}
