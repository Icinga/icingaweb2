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

use \Icinga\Protocol\Commandpipe\Exception\InvalidCommandException;
use \Icinga\Protocol\Commandpipe\Comment;

/**
 * Container for a host/service Acknowledgement
 */
class Acknowledgement implements IComment
{
    /**
     * The expire time of this acknowledgement or -1 if no expire time is used
     *
     * @var int
     */
    private $expireTime = -1;

    /**
     * Whether to set the notify flag of the acknowledgment
     *
     * @var bool
     */
    private $notify = false;

    /**
     * The comment text of this acknowledgment
     *
     * @var Comment
     */
    private $comment;

    /**
     * true if this is a sticky acknowledgment
     *
     * @var bool
     */
    public $sticky;

    /**
     * Set the expire time of this acknowledgment to $time
     *
     * @param int $time     The new expire time as a UNIX timestamp
     */
    public function setExpireTime($time)
    {
        $this->expireTime = intval($time);
    }

    /**
     * Set the notify flag of this object
     *
     * @param boolean $bool     True if notify should be set, otherwise false
     */
    public function setNotify($bool)
    {
        $this->notify = (bool)$bool;
    }

    /**
     * Create a new acknowledgment container
     *
     * @param Comment $comment      The comment to use for the acknowledgement
     * @param bool $notify          Whether to set the notify flag
     * @param int  $expire          The expire time or -1 of not expiring
     * @param bool $sticky          Whether to set the sticky flag
     */
    public function __construct(Comment $comment, $notify = false, $expire = -1, $sticky = false)
    {
        $this->comment = $comment;
        $this->setNotify($notify);
        $this->setExpireTime($expire);
        $this->sticky = $sticky;
    }

    /**
     * Return the ACKNOWLEDGE_?_PROBLEM string to be used for submitting an external icinga command
     *
     * @param  string $type Either CommandPipe::TYPE_HOST or CommandPipe::TYPE_SERVICE
     * @return string       The command string to be submitted to the command pipe
     * @throws InvalidCommandException
     */
    public function getFormatString($type)
    {
        $params = ';'
            . ($this->sticky ? '2' : '0')
            . ';' . ($this->notify ? '1 ' : '0')
            . ';' . ($this->comment->persistent ? '1' : '0');

        $params .= ($this->expireTime > -1 ? ';'. $this->expireTime . ';' : ';')
            . $this->comment->author . ';' . $this->comment->comment;

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

        $base = "ACKNOWLEDGE_{$typeVar}_PROBLEM" . ($this->expireTime > -1 ? '_EXPIRE' : '');
        return $base . $params;
    }
}
