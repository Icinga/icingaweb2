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

namespace Tests\Icinga\Application;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
// @codingStandardsIgnoreEnd

use \Zend_Config;
use Icinga\Application\Logger;
use Icinga\Test\BaseTestCase;

/**
 * Test class for Logger
 *
 * @backupStaticAttributes enabled
 **/
class LoggerTest extends BaseTestCase
{
    private $tempDir;

    private $logTarget;

    private $debugTarget;

    public function setUp()
    {
        $this->tempDir = tempnam(sys_get_temp_dir(), 'icingaweb-log');
        unlink($this->tempDir); // tempnam create the file automatically

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755);
        }

        $this->debugTarget = $this->tempDir . '/debug.log';
        $this->logTarget = $this->tempDir . '/application.log';

        $loggingConfigurationArray = array(
            'enable'    => 1,
            'type'      => 'stream',
            'verbose'   => 1,
            'target'    => $this->logTarget,

            'debug'     => array(
                'enable'    => 1,
                'type'      => 'stream',
                'target'    => $this->debugTarget
            )
        );

        $loggingConfiguration = new Zend_Config($loggingConfigurationArray);

        Logger::reset();
        Logger::create($loggingConfiguration);

    }

    public function tearDown()
    {
        if (file_exists($this->debugTarget)) {
            unlink($this->debugTarget);
        }

        if (file_exists($this->logTarget)) {
            unlink($this->logTarget);
        }

        rmdir($this->tempDir);
    }

    private function getLogData()
    {
        return array(
            explode(PHP_EOL, file_get_contents($this->logTarget)),
            explode(PHP_EOL, file_get_contents($this->debugTarget))
        );
    }

    /**
     * Test error messages
     */
    public function testLoggingErrorMessages()
    {
        Logger::error('test-error-1');
        Logger::error('test-error-2');

        $this->assertFileExists($this->logTarget);
        $this->assertFileExists($this->debugTarget);

        list($main, $debug) = $this->getLogData();

        $this->assertCount(3, $main);
        $this->assertCount(3, $debug);

        $this->assertContains(' ERR (3): test-error-1', $main[0]);
        $this->assertContains(' ERR (3): test-error-2', $main[1]);

        $this->assertContains(' ERR (3): test-error-1', $debug[0]);
        $this->assertContains(' ERR (3): test-error-2', $debug[1]);
    }

    /**
     * Test debug log and difference between error and debug messages
     */
    public function testLoggingDebugMessages()
    {
        Logger::debug('test-debug-1');
        Logger::error('test-error-1');
        Logger::debug('test-debug-2');

        $this->assertFileExists($this->logTarget);
        $this->assertFileExists($this->debugTarget);

        list($main, $debug) = $this->getLogData();

        $this->assertCount(2, $main);
        $this->assertCount(4, $debug);

        $this->assertContains(' ERR (3): test-error-1', $main[0]);

        $this->assertContains(' DEBUG (7): test-debug-1', $debug[0]);
        $this->assertContains(' ERR (3): test-error-1', $debug[1]);
        $this->assertContains(' DEBUG (7): test-debug-2', $debug[2]);
    }

    public function testLoggingQueueIfNoWriterAvailable()
    {
        Logger::reset();

        Logger::error('test-error-1');
        Logger::debug('test-debug-1');
        Logger::error('test-error-2');

        list($main, $debug) = $this->getLogData();

        $this->assertCount(1, $main);
        $this->assertCount(1, $debug);

        $this->assertTrue(Logger::hasErrorsOccurred());

        $queue = Logger::getQueue();

        $this->assertCount(3, $queue);

        $this->assertEquals(
            array(
                'test-error-1',
                3
            ),
            $queue[0]
        );

        $this->assertEquals(
            array(
                'test-debug-1',
                7
            ),
            $queue[1]
        );

        $this->assertEquals(
            array(
                'test-error-2',
                3
            ),
            $queue[2]
        );
    }
}
