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

namespace Icinga\Protocol\Commandpipe;

/**
 * Container for comment information that can be send to icinga's external command pipe
 *
 */
class Comment implements IComment
{
    /**
     * Whether the persistent flag should be submitted with this command
     *
     * @var bool
     */
    public $persistent = false;

    /**
     *  The author of this comment
     *
     *  @var string
     */
    public $author = "";

    /**
     *  The comment text to use
     *
     *  @var string
     */
    public $comment = "";

    /**
     * Create a new comment object
     *
     * @param string $author    The author name to use for this object
     * @param string $comment   The comment text to use
     * @param bool $persistent  Whether this comment should persist icinga restarts
     */
    public function __construct($author, $comment, $persistent = false)
    {
        $this->author = $author;
        $this->comment = $comment;
        $this->persistent = $persistent;
    }

    /**
     * Return this comment as an ADD_?_COMMENT external command string that can directly be send to the command pipe
     *
     * @param string $type                  either CommandPipe::TYPE_HOST or CommandPipe::TYPE_SERVICE
     *
     * @return string                       The ADD_HOST_COMMENT or ADD_SVC_COMMENT command, without the timestamp
     *
     * @throws InvalidCommandException      When $type is unknown
     */
    public function getFormatString($type)
    {
        $params = ';' . ($this->persistent ? '1' : '0') . ';' . $this->author . ';' . $this->comment;

        switch ($type) {
            case CommandPipe::TYPE_HOST:
                $typeVar = "HOST";
                $params = ";%s" . $params;
                break;
            case CommandPipe::TYPE_SERVICE:
                $typeVar = "SVC";
                $params = ";%s;%s" . $params;
                break;
            default:
                throw new InvalidCommandException("Acknowledgements can only apply on hosts and services ");
        }
        return "ADD_{$typeVar}_COMMENT$params";
    }
}
