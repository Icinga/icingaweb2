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

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
// @codingStandardsIgnoreEnd

use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once BaseTestCase::$libDir . '/Logger/Logger.php';
// @codingStandardsIgnoreEnd

use \Zend_Config;
use Icinga\Logger\Logger;

class LoggerTest extends BaseTestCase
{
    /**
     * @backupStaticAttributes enabled
     */
    public function testLogfileCreation()
    {
        $target = tempnam(sys_get_temp_dir(), 'log');
        unlink($target);
        Logger::create(
            new Zend_Config(
                array(
                    'enable'    => true,
                    'level'     => Logger::$ERROR,
                    'type'      => 'stream',
                    'target'    => $target
                )
            )
        );
        $this->assertFileExists($target, 'Logger did not create the log file');
        unlink($target);
    }

    /**
     * @backupStaticAttributes  enabled
     * @depends                 testLogfileCreation
     */
    public function testLoggingErrorMessages()
    {
        $target = tempnam(sys_get_temp_dir(), 'log');
        unlink($target);
        Logger::create(
            new Zend_Config(
                array(
                    'enable'    => true,
                    'level'     => Logger::$ERROR,
                    'type'      => 'stream',
                    'target'    => $target
                )
            )
        );
        Logger::error('This is a test error');
        $log = file_get_contents($target);
        unlink($target);
        $this->assertContains('This is a test error', $log, 'Log does not contain the error "This is a test error"');
    }
}
