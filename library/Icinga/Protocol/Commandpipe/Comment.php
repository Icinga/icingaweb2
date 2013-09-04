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
 */
class Comment
{
    /**
     * Whether this comment is persistent or not
     *
     * @var bool
     */
    public $persistent;

    /**
     * The author of this comment
     *
     * @var string
     */
    public $author;

    /**
     * The text of this comment
     *
     * @var string
     */
    public $content;

    /**
     * Create a new comment object
     *
     * @param   string  $author         The name of the comment's author
     * @param   string  $content        The text for this comment
     * @param   bool    $persistent     Whether this comment should be persistent or not
     */
    public function __construct($author, $content, $persistent = false)
    {
        $this->author = $author;
        $this->content = $content;
        $this->persistent = $persistent;
    }

    /**
     * Return this comment's properties as list of command parameters
     *
     * @param   bool    $ignorePersistentFlag   Whether the persistent flag should be included or not
     * @return  array
     */
    public function getParameters($ignorePersistentFlag = false)
    {
        if ($ignorePersistentFlag) {
            return array($this->author, $this->content);
        } else {
            return array($this->persistent ? '1' : '0', $this->author, $this->content);
        }
    }
}
