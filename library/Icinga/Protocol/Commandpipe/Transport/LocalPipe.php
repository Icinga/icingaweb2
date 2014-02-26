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

namespace Icinga\Protocol\Commandpipe\Transport;

use Icinga\Logger\Logger;

/**
 * CommandPipe Transport class that writes to a file accessible by the filesystem
 */
class LocalPipe implements Transport
{
    /**
     * The path of the icinga commandpipe
     *
     * @var String
     */
    private $path;

    /**
     * The mode to use for fopen()
     *
     * @var string
     */
    private $openMode = "w";

    /**
     * @see Transport::setEndpoint()
     */
    public function setEndpoint(\Zend_Config $config)
    {
        $this->path = isset($config->path) ? $config->path : '/usr/local/icinga/var/rw/icinga.cmd';
    }

    /**
     *  @see Transport::send()
     */
    public function send($message)
    {
        Logger::debug('Attempting to send external icinga command %s to local command file ', $message, $this->path);
        $file = @fopen($this->path, $this->openMode);
        if (!$file) {
            throw new \RuntimeException('Could not open icinga pipe at $file : ' . print_r(error_get_last(), true));
        }
        fwrite($file, '[' . time() . '] ' . $message . PHP_EOL);
        Logger::debug('Writing [' . time() . '] ' . $message . PHP_EOL);
        fclose($file);
    }

    /**
     * Overwrite the open mode (useful for testing)
     *
     * @param string $mode          A open mode supported by fopen()
     */
    public function setOpenMode($mode)
    {
        $this->openMode = $mode;
    }
}
