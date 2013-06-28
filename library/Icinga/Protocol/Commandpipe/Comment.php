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

namespace Icinga\Protocol\Commandpipe;

/**
 * Class Comment
 * @package Icinga\Protocol\Commandpipe
 */
class Comment implements IComment
{
    /**
     * @var bool
     */
    public $persistent = false;

    /**
     * @var string
     */
    public $author = "";

    /**
     * @var string
     */
    public $comment = "";

    /**
     * @param $author
     * @param $comment
     * @param bool $persistent
     */
    public function __construct($author, $comment, $persistent = false)
    {
        $this->author = $author;
        $this->comment = $comment;
        $this->persistent = $persistent;
    }

    /**
     * @param $type
     * @return string
     * @throws InvalidCommandException
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
