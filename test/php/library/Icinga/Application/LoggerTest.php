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

namespace Tests\Icinga\Application;

require_once 'Zend/Log.php';
require_once 'Zend/Config.php';
require_once 'Zend/Log/Writer/Mock.php';
require_once 'Zend/Log/Writer/Null.php';
require_once 'Zend/Log/Filter/Priority.php';

require_once realpath(__DIR__  . '/../../../../../library/Icinga/Application/Logger.php');
require_once realpath(__DIR__  . '/../../../../../library/Icinga/Exception/ConfigurationError.php');

use \Icinga\Application\Logger;

/**
 * Test class for Logger
 **/
class LoggerTest extends \PHPUnit_Framework_TestCase
{
    private $timeZone;

    protected function setUp()
    {
        date_default_timezone_set('GMT');
    }

    public function testOverwrite()
    {
        $cfg1 = new \Zend_Config(
            array(
                'debug'     => array('enable' => 0),
                'type'      => 'mock',
                'target'    => 'target2'
            )
        );
        $cfg2 = new \Zend_Config(
            array(
                'debug'     => array(
                    'enable' => 1,
                    'type'=>'mock',
                    'target'=>'target3'
                ),
                'type'      => 'mock',
                'target'    => 'target4'
            )
        );

        $logger = new Logger($cfg1);
        $writers = $logger->getWriters();
        $this->assertEquals(1, count($writers));

        $logger = new Logger($cfg1);
        $writers2 = $logger->getWriters();
        $this->assertEquals(1, count($writers));
        $this->assertNotEquals($writers[0], $writers2[0]);

        $logger = new Logger($cfg2);
        $writers2 = $logger->getWriters();
        $this->assertEquals(2, count($writers2));
    }

    public function testFormatMessage()
    {
        $message = Logger::formatMessage(array('Testmessage'));
        $this->assertEquals('Testmessage', $message);

        $message = Logger::formatMessage(array('Testmessage %s %s', 'test1', 'test2'));
        $this->assertEquals('Testmessage test1 test2', $message);

        $message = Logger::formatMessage(array('Testmessage %s', array('test1', 'test2')));
        $this->assertEquals('Testmessage '.json_encode(array('test1', 'test2')), $message);
    }

    /**
     * @backupStaticAttributes enabled
     */
    public function testLoggingOutput()
    {
        $cfg1 = new \Zend_Config(
            array(
                'debug'     => array('enable' => 0),
                'type'      => 'mock',
                'target'    => 'target2'
            )
        );

        $logger = Logger::create($cfg1);
        $writers = $logger->getWriters();

        $logger->warn('Warning');
        $logger->error('Error');
        $logger->info('Info');
        $logger->debug('Debug');

        $writer = $writers[0];
        $this->assertEquals(2, count($writer->events));
        $this->assertEquals($writer->events[0]['message'], 'Warning');
        $this->assertEquals($writer->events[1]['message'], 'Error');

    }

    /**
     * @backupStaticAttributes enabled
     */
    public function testLogQueuing()
    {
        $cfg1 = new \Zend_Config(
            array(
                'debug'     => array('enable' => 0),
                'type'      => 'mock',
                'target'    => 'target2'
            )
        );

        Logger::warn('Warning');
        Logger::error('Error');
        Logger::info('Info');
        Logger::debug('Debug');

        $logger = Logger::create($cfg1);
        $writers = $logger->getWriters();
        $writer = $writers[0];

        $this->assertEquals(2, count($writer->events));
        $this->assertEquals($writer->events[0]['message'], 'Warning');
        $this->assertEquals($writer->events[1]['message'], 'Error');
    }

    /**
     * @backupStaticAttributes enabled
     */
    public function testDebugLogErrorCatching()
    {
        $cfg1 = new \Zend_Config(
            array(
                'debug'     => array(
                    'enable'   => 1,
                    'type'      => 'Invalid',
                    'target'    => '...'
                ),
                'type'      => 'mock',
                'target'    => 'target2'
            )
        );

        $logger = Logger::create($cfg1);
        $writers = $logger->getWriters();
        $this->assertEquals(1, count($writers));
        $this->assertEquals(1, count($writers[0]->events));
        $this->assertEquals(
            'Could not add log writer of type "Invalid". Type does not exist.',
            $writers[0]->events[0]['message']
        );
    }
    /**
     * @backupStaticAttributes enabled
     */
    public function testNotLoggedMessagesQueue()
    {
        $cfg1 = new \Zend_Config(
            array(
                'debug'     => array(
                    'enable'   => 0,
                    'type'      => 'Invalid',
                    'target'    => '...'
                ),
                'type'      => 'invalid',
                'target'    => 'target2'
            )
        );

        $logger = Logger::create($cfg1);

        $this->assertTrue(Logger::hasErrorsOccurred());

        $queue = Logger::getQueue();

        $this->assertCount(2, $queue);

        $this->assertSame(
            'Could not add log writer of type "Invalid". Type does not exist.',
            $queue[0][0],
            'Log message of an invalid writer'
        );

        $this->assertSame(0, $queue[0][1], 'Log level "fatal"');

        $this->assertSame(
            'Could not flush logs to output. An exception was thrown: No writers were added',
            $queue[1][0],
            'Log message that no writer was added to logger'
        );

        $this->assertSame(0, $queue[1][1], 'Log level "fatal"');
    }
}
