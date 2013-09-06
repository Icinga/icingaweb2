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

require_once(realpath(__DIR__ . '/CommandPipeLoader.php'));
CommandPipeLoader::requireLibrary();

use Zend_Config;
use PHPUnit_Framework_TestCase;
use Icinga\Protocol\Commandpipe\Comment;
use Icinga\Protocol\Commandpipe\Commandpipe as Commandpipe;
use Icinga\Protocol\Commandpipe\PropertyModifier as MONFLAG;
use Icinga\Protocol\Ldap\Exception;
use Icinga\Module\Monitoring\Command\AcknowledgeCommand;
use Icinga\Module\Monitoring\Command\AddCommentCommand;
use Icinga\Module\Monitoring\Command\ScheduleDowntimeCommand;
use Icinga\Module\Monitoring\Command\CustomNotificationCommand;
use Icinga\Module\Monitoring\Command\DelayNotificationCommand;
use Icinga\Module\Monitoring\Command\ScheduleCheckCommand;
use Icinga\Module\Monitoring\Command\SubmitPassiveCheckresultCommand;

if (!defined('EXTCMD_TEST_BIN')) {
    define('EXTCMD_TEST_BIN', './bin/extcmd_test');
}

/**
 * Several tests for the command pipe component
 *
 * Uses the helper script extcmd_test, which is basically the extracted command
 * parser functions from the icinga core
 */
class CommandPipeTest extends PHPUnit_Framework_TestCase
{
    /**
     * Return the path of the test pipe used in these tests
     *
     * @return string
     */
    public function getPipeName()
    {
        return sys_get_temp_dir() . '/icinga_test_pipe';
    }

    /**
     * Return a @see Icinga\Protocal\CommandPipe\CommandPipe instance set up for the local test pipe
     *
     * @return Commandpipe
     */
    private function getLocalTestPipe()
    {
        $tmpPipe = $this->getPipeName();
        $this->cleanup();
        touch($tmpPipe);

        $cfg = new Zend_Config(
            array(
                'path' => $tmpPipe,
                'name' => 'test'
            )
        );

        return new Commandpipe($cfg);
    }

    /**
     * Return a @see Icinga\Protocal\CommandPipe\CommandPipe instance set up
     * for the local test pipe, but with ssh as the transport layer
     *
     * @return Commandpipe
     */
    private function getSSHTestPipe()
    {
        $tmpPipe = $this->getPipeName();
        $this->cleanup();
        touch($tmpPipe);

        $cfg = new Zend_Config(
            array(
                'path'      => $tmpPipe,
                'user'      => 'vagrant',
                'password'  => 'vagrant',
                'host'      => 'localhost',
                'port'      => 22,
                'name'      => 'test'
            )
        );

        return new Commandpipe($cfg);
    }

    /**
     * Remove the testpipe if it exists
     */
    private function cleanup() {
        if (file_exists($this->getPipeName())) {
            unlink($this->getPipeName());
        }
    }

    /**
     * Query the extcmd_test script with $command or the command pipe and test whether the result is $exceptedString and
     * has a return code of 0.
     *
     * Note:
     * - if no string is given, only the return code is tested
     * - if no command is given, the content of the test commandpipe is used
     *
     * This helps testing whether commandpipe serialization works correctly
     *
     * @param bool $expectedString      The string that is expected to be returned from the extcmd_test binary
     *                                  (optional, leave it to just test for the return code)
     * @param bool $command             The commandstring to send (optional, leave it for using the command pipe content)
     */
    private function assertCommandSucceeded($expectedString = false, $command = false) {
        $resultCode = null;
        $resultArr = array();
        $receivedCmd = exec(EXTCMD_TEST_BIN . ' ' . escapeshellarg(
            $command ? $command : file_get_contents($this->getPipeName())),
            $resultArr,
            $resultCode
        );
        $this->assertEquals(0, $resultCode, 'Submit of external command returned error : ' . $receivedCmd);
        if ($expectedString) {
            $this->assertEquals(
                $expectedString,
                $receivedCmd,
                'Asserting that the command icinga received matches the command we send'
            );
        }
    }

    /**
     * Test whether a single host acknowledgment is serialized and send correctly
     *
     * @throws Exception
     */
    public function testAcknowledgeSingleHost()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $ack = new AcknowledgeCommand(new Comment('I can', 'sends teh ack'));
            $pipe->sendCommand(
                $ack,
                array(
                    (object) array(
                        'host_name' => 'hostA'
                    )
                )
            );
            $this->assertCommandSucceeded('ACKNOWLEDGE_HOST_PROBLEM;hostA;0;0;0;I can;sends teh ack');
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether multiple host and service acknowledgments are serialized and send correctly
     *
     * @throws Exception
     */
    public function testAcknowledgeMultipleObjects()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $ack = new AcknowledgeCommand(new Comment('I can', 'sends teh ack'));
            $pipe->getTransport()->setOpenMode('a');
            $pipe->sendCommand(
                $ack,
                array(
                    (object) array(
                        'host_name'           => 'hostA'
                    ),(object) array(
                        'host_name'           => 'hostB'
                    ),(object) array(
                        'host_name'           => 'hostC'
                    ),(object) array(
                        'host_name'           => 'hostC',
                        'service_description' => 'svc'
                    )
                )
            );

            $result = explode("\n", file_get_contents($this->getPipeName()));
            $this->assertCount(5, $result, 'Asserting the correct number of commands being written to the command pipe');

            $this->assertCommandSucceeded('ACKNOWLEDGE_HOST_PROBLEM;hostA;0;0;0;I can;sends teh ack', $result[0]);
            $this->assertCommandSucceeded('ACKNOWLEDGE_HOST_PROBLEM;hostB;0;0;0;I can;sends teh ack', $result[1]);
            $this->assertCommandSucceeded('ACKNOWLEDGE_HOST_PROBLEM;hostC;0;0;0;I can;sends teh ack', $result[2]);
            $this->assertCommandSucceeded('ACKNOWLEDGE_SVC_PROBLEM;hostC;svc;0;0;0;I can;sends teh ack', $result[3]);

        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether a single host comment is correctly serialized and send to the command pipe
     *
     * @throws Exception
     */
    public function testAddHostComment()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->sendCommand(
                new AddCommentCommand(
                    new Comment(
                        'Autor',
                        'Comment'
                    )
                ),
                array(
                    (object) array(
                        'host_name' => 'hostA'
                    )
                )
            );
            $this->assertCommandSucceeded('ADD_HOST_COMMENT;hostA;0;Autor;Comment');
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether removing all hostcomments is correctly serialized and send to the command pipe
     *
     * @throws Exception
     */
    public function testRemoveAllHostComment()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->removeComment(
                array(
                    (object) array(
                        'host_name' => 'test'
                    )
                )
            );
            $this->assertCommandSucceeded('DEL_ALL_HOST_COMMENTS;test');
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether removing a single host comment is correctly serialized and send to the command pipe
     *
     * @throws Exception
     */
    public function testRemoveSpecificComment()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->removeComment(
                array(
                    (object) array(
                        'comment_id' => 34,
                        'host_name'  => 'test'
                    )
                )
            );
            $this->assertCommandSucceeded('DEL_HOST_COMMENT;34');
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether a multiple reschedules for services and hosts are correctly serialized and send to the commandpipe
     *
     * @throws Exception
     */
    public function testScheduleChecks()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->getTransport()->setOpenMode('a'); // append so we have multiple results
            $command = new ScheduleCheckCommand(5000);
            $pipe->sendCommand(
                $command,
                array(
                    (object) array(
                        'host_name'           => 'test'
                    ),
                    (object) array(
                        'host_name'           => 'test',
                        'service_description' => 'svc1'
                    )
                )
            );
            $command->setForced(true);
            $pipe->sendCommand(
                $command,
                array(
                    (object) array(
                        'host_name'           => 'test'
                    ),
                    (object) array(
                        'host_name'           => 'test',
                        'service_description' => 'svc1'
                    )
                )
            );
            $command->excludeHost();
            $pipe->sendCommand(
                $command,
                array(
                    (object) array(
                        'host_name' => 'test'
                    )
                )
            );

            $result = explode("\n", file_get_contents($this->getPipeName()));
            $this->assertCount(6, $result, 'Asserting a correct number of commands being written to the commandpipe');

            $this->assertCommandSucceeded('SCHEDULE_HOST_CHECK;test;5000', $result[0]);
            $this->assertCommandSucceeded('SCHEDULE_SVC_CHECK;test;svc1;5000', $result[1]);
            $this->assertCommandSucceeded('SCHEDULE_FORCED_HOST_CHECK;test;5000', $result[2]);
            $this->assertCommandSucceeded('SCHEDULE_FORCED_SVC_CHECK;test;svc1;5000', $result[3]);
            $this->assertCommandSucceeded('SCHEDULE_FORCED_HOST_SVC_CHECKS;test;5000', $result[4]);
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether modifying monitoringflags of a host and service is correctly serialized and send to the command pipe
     *
     * @throws Exception
     */
    public function testObjectStateModifications()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->getTransport()->setOpenMode('a');
            $pipe->setMonitoringProperties(
                array(
                    (object) array(
                        'host_name'           => 'Testhost'
                    ),
                    (object) array(
                        'host_name'           => 'host',
                        'service_description' => 'svc'
                    )
                ), new MONFLAG(
                    array(
                        MONFLAG::ACTIVE         => MONFLAG::STATE_DISABLE,
                        MONFLAG::PASSIVE        => MONFLAG::STATE_ENABLE,
                        MONFLAG::NOTIFICATIONS  => MONFLAG::STATE_DISABLE,
                        MONFLAG::EVENTHANDLER   => MONFLAG::STATE_ENABLE,
                        MONFLAG::FLAPPING       => MONFLAG::STATE_DISABLE,
                        MONFLAG::FRESHNESS      => MONFLAG::STATE_ENABLE,
                    )
                )
            );

            $result = explode("\n", file_get_contents($this->getPipeName()));
            array_pop($result); // remove empty last line
            $this->assertCount(12, $result, 'Asserting a correct number of commands being written to the commandpipe');
            foreach ($result as $command) {
                $this->assertCommandSucceeded(false, $command);
            }

        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether enabling and disabling global notifications are send correctly to the pipe
     *
     * @throws Exception
     */
    public function testGlobalNotificationTrigger()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->enableGlobalNotifications();
            $this->assertCommandSucceeded('ENABLE_NOTIFICATIONS;');
            $pipe->disableGlobalNotifications();
            $this->assertCommandSucceeded('DISABLE_NOTIFICATIONS;');
        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether host and servicedowntimes are correctly scheduled
     *
     * @throws Exception
     */
    public function testScheduleDowntime()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $downtime = new ScheduleDowntimeCommand(25, 26, new Comment('me', 'test'));
            $pipe->sendCommand(
                $downtime,
                array(
                    (object) array(
                        'host_name' => 'Testhost'
                    )
                )
            );
            $this->assertCommandSucceeded('SCHEDULE_HOST_DOWNTIME;Testhost;25;26;1;0;0;me;test');

            $pipe->sendCommand(
                $downtime,
                array(
                    (object) array(
                        'host_name' => 'Testhost',
                        'service_description' => 'svc'
                    )
                )
            );
            $this->assertCommandSucceeded('SCHEDULE_SVC_DOWNTIME;Testhost;svc;25;26;1;0;0;me;test');

            $downtime->excludeHost();
            $pipe->sendCommand(
                $downtime,
                array(
                    (object) array(
                        'host_name' => 'Testhost'
                    )
                )
            );
            $this->assertCommandSucceeded('SCHEDULE_HOST_SVC_DOWNTIME;Testhost;25;26;1;0;0;me;test');
        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether propagated host downtimes are correctly scheduled
     *
     * @throws Exception
     */
    public function testSchedulePropagatedDowntime()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $downtime = new ScheduleDowntimeCommand(25, 26, new Comment('me', 'test'));
            $downtime->includeChildren();
            $pipe->sendCommand(
                $downtime,
                array(
                    (object) array(
                        'host_name' => 'Testhost'
                    )
                )
            );
            $this->assertCommandSucceeded('SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME;Testhost;25;26;1;0;0;me;test');

            $downtime->includeChildren(true, true);
            $pipe->sendCommand(
                $downtime,
                array(
                    (object) array(
                        'host_name' => 'Testhost'
                    )
                )
            );
            $this->assertCommandSucceeded(
                'SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME;Testhost;25;26;1;0;0;me;test'
            );
        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether the removal of downtimes is correctly serialized and send to the commandpipe for hosts and services
     *
     * @throws Exception
     */
    public function testRemoveDowntime()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->getTransport()->setOpenMode('a');
            $pipe->removeDowntime(
                array(
                    (object) array(
                        'host_name'             => 'Testhost'
                    ),
                    (object) array(
                        'host_name'             => 'host',
                        'service_description'   => 'svc'
                    ),
                    (object) array(
                        'host_name'             => 'host',
                        'service_description'   => 'svc',
                        'downtime_id'           => 123
                    )
                )
            );
            $result = explode("\n", file_get_contents($this->getPipeName()));
            array_pop($result); // remove empty last line
            $this->assertCount(3, $result, 'Asserting a correct number of commands being written to the commandpipe');
            $this->assertCommandSucceeded('DEL_DOWNTIME_BY_HOST_NAME;Testhost', $result[0]);
            $this->assertCommandSucceeded('DEL_DOWNTIME_BY_HOST_NAME;host;svc', $result[1]);
            $this->assertCommandSucceeded('DEL_SVC_DOWNTIME;123', $result[2]);

        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether custom  servicenotifications are correctly send to the commandpipe without options
     *
     * @throws Exception
     */
    public function testSendCustomServiceNotification()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $notification = new CustomNotificationCommand(new Comment('Author', 'Comment'));
            $pipe->sendCommand(
                $notification,
                array(
                    (object) array(
                        'host_name'             => 'Host',
                        'service_description'   => 'Service'
                    )
                )
            );
            $this->assertCommandSucceeded('SEND_CUSTOM_SVC_NOTIFICATION;Host;Service;0;Author;Comment');
        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether custom hostnotifications are correctly send to the commandpipe with a varlist of options
     *
     * @throws Exception
     */
    public function testSendCustomHostNotificationWithOptions()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $notification = new CustomNotificationCommand(new Comment('Author', 'Comment'), true, true);
            $pipe->sendCommand(
                $notification,
                array(
                    (object) array(
                        'host_name'             => 'Host',
                        'service_description'   => 'Service'
                    )
                )
            );
            $this->assertCommandSucceeded('SEND_CUSTOM_SVC_NOTIFICATION;Host;Service;3;Author;Comment');
        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether commands to delay notifications are being sent to the commandpipe
     *
     * @throws Exception
     */
    public function testDelayNotification()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $delay = new DelayNotificationCommand(300);
            $pipe->sendCommand(
                $delay,
                array(
                    (object) array(
                        'host_name'             => 'Host',
                        'service_description'   => 'Service'
                    )
                )
            );
            $this->assertCommandSucceeded('DELAY_SVC_NOTIFICATION;Host;Service;300');

            $pipe->sendCommand(
                $delay,
                array(
                    (object) array(
                        'host_name'             => 'Host'
                    )
                )
            );
            $this->assertCommandSucceeded('DELAY_HOST_NOTIFICATION;Host;300');
        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether commands to submit passive check results are being sent to the commandpipe
     *
     * @throws Exception
     */
    public function testSubmitPassiveCheckresult()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $result = new SubmitPassiveCheckresultCommand(0, 'foo', 'bar');
            $pipe->sendCommand(
                $result,
                array(
                    (object) array(
                        'host_name'             => 'Host',
                        'service_description'   => 'Service'
                    )
                )
            );
            $this->assertCommandSucceeded('PROCESS_SERVICE_CHECK_RESULT;Host;Service;0;foo|bar');

            $result->setOutput('foobar');
            $result->setPerformanceData('');
            $pipe->sendCommand(
                $result,
                array(
                    (object) array(
                        'host_name' => 'Host'
                    )
                )
            );
            $this->assertCommandSucceeded('PROCESS_HOST_CHECK_RESULT;Host;0;foobar');
        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test sending of commands via SSH (currently disabled)
     *
     * @throws Exception
     */
    public function testSSHCommands()
    {
        $this->markTestSkipped('This test assumes running in a vagrant VM with key-auth');

        $pipe = $this->getSSHTestPipe();
        try {
            $ack = new AcknowledgeCommand(new Comment('I can', 'sends teh ack'));
            $pipe->sendCommand(
                $ack,
                array(
                    (object) array(
                        'host_name' => 'hostA'
                    )
                )
            );
            $this->assertCommandSucceeded('ACKNOWLEDGE_HOST_PROBLEM;hostA;0;0;0;I can;sends teh ack');
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }
}
