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

namespace Icinga\Module\Monitoring\Command;

use Icinga\Protocol\Commandpipe\Comment;

class AddCommentCommand extends BaseCommand
{
    /**
     * The comment associated to this command
     *
     * @var Comment
     */
    private $comment;

    /**
     * Initialise a new command object to add comments
     *
     * @param   Comment $comment    The comment to use for this acknowledgement
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Set the comment for this command
     *
     * @param   Comment     $comment
     * @return  self
     */
    public function setComment(Comment $comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @see BaseCommand::getHostCommand()
     */
    public function getHostCommand($hostname)
    {
        return sprintf('ADD_HOST_COMMENT;%s;', $hostname) . implode(';', $this->comment->getParameters());
    }

    /**
     * @see BaseCommand::getServiceCommand()
     */
    public function getServiceCommand($hostname, $servicename)
    {
        return sprintf('ADD_SVC_COMMENT;%s;%s;', $hostname, $servicename)
            . implode(';', $this->comment->getParameters());
    }
}
