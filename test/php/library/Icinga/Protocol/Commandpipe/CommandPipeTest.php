<?php
namespace Tests\Icinga\Protocol\Commandpipe;

require_once(__DIR__.'/CommandPipeLoader.php');
CommandPipeLoader::requireLibrary();

use Icinga\Protocol\Commandpipe\Comment as Comment;
use Icinga\Protocol\Commandpipe\Acknowledgement as Acknowledgement;
use Icinga\Protocol\Commandpipe\Downtime as Downtime;
use Icinga\Protocol\Commandpipe\Commandpipe as Commandpipe;
use \Icinga\Protocol\Commandpipe\PropertyModifier as MONFLAG;
use Icinga\Protocol\Ldap\Exception;

if(!defined("EXTCMD_TEST_BIN"))
    define("EXTCMD_TEST_BIN", "./bin/extcmd_test");

/**
 * Several tests for the command pipe component
 *
 * Uses the helper script extcmd_test, which is basically the extracted command
 * parser functions from the icinga core
 *
 *
 */
class CommandPipeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Return the path of the test pipe used in these tests
     *
     * @return string
     */
    public function getPipeName()
    {
        return sys_get_temp_dir()."/icinga_test_pipe";
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

        $cfg = new \Zend_Config(array(
            "path" => $tmpPipe,
            "name" => "test"
        ));
        $comment = new Comment("Autor","Comment");
        $pipe = new Commandpipe($cfg);

        return $pipe;
    }

    /**
     * Return a @see Icinga\Protocal\CommandPipe\CommandPipe instance set up for the local test pipe, but with ssh as the transport layer
     *
     * @return Commandpipe
     */
    private function getSSHTestPipe()
    {
        $tmpPipe = $this->getPipeName();
        $this->cleanup();
        touch($tmpPipe);

        $cfg = new \Zend_Config(array(
            "path" => $tmpPipe,
            "user"  => "vagrant",
            "password" => "vagrant",
            "host" => 'localhost',
            "port"  => 22,
            "name" => "test"
        ));
        $comment = new Comment("Autor","Comment");
        $pipe = new Commandpipe($cfg);

        return $pipe;
    }

    /**
     * Remove the testpipe if it exists
     *
     */
    private function cleanup() {
        if(file_exists($this->getPipeName()))
            unlink($this->getPipeName());
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
    private function assertCommandSucceeded($expectedString = false,$command = false) {
        $resultCode = null;
        $resultArr = array();
        $receivedCmd = exec(EXTCMD_TEST_BIN." ".escapeshellarg($command ? $command : file_get_contents($this->getPipeName())),$resultArr,$resultCode);
        $this->assertEquals(0, $resultCode, "Submit of external command returned error : ".$receivedCmd);
        if (!$expectedString)
           return;
        $this->assertEquals(
            $expectedString,
            $receivedCmd,
            'Asserting that the command icinga received matches the command we send'
        );
    }

    /**
     * Test whether a single host acknowledgment is serialized and send correctly
     *
     * @throws \Exception|Exception
     */
    public function testAcknowledgeSingleHost()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $ack = new Acknowledgement(new Comment("I can","sends teh ack"));
            $pipe->acknowledge(array(
                (object) array(
                    "host_name" => "hostA"
                )
            ),$ack);
            $this->assertCommandSucceeded("ACKNOWLEDGE_HOST_PROBLEM;hostA;0;0;0;I can;sends teh ack");
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether multiple host and service acknowledgments are serialized and send correctly
     *
     * @throws \Exception|Exception
     */
    public function testAcknowledgeMultipleObjects()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $ack = new Comment("I can","sends teh ack");
            $pipe->getTransport()->setOpenMode("a");
            $pipe->acknowledge(array(
                (object) array(
                    "host_name" => "hostA"
                ),(object) array(
                    "host_name" => "hostB"
                ),(object) array(
                    "host_name" => "hostC"
                ),(object) array(
                    "host_name" => "hostC",
                    "service_description" => "svc"
                )
            ),$ack);

            $result = explode("\n",file_get_contents($this->getPipeName()));
            $this->assertCount(5, $result, "Asserting the correct number of commands being written to the command pipe");

            $this->assertCommandSucceeded("ACKNOWLEDGE_HOST_PROBLEM;hostA;0;0;0;I can;sends teh ack",$result[0]);
            $this->assertCommandSucceeded("ACKNOWLEDGE_HOST_PROBLEM;hostB;0;0;0;I can;sends teh ack",$result[1]);
            $this->assertCommandSucceeded("ACKNOWLEDGE_HOST_PROBLEM;hostC;0;0;0;I can;sends teh ack",$result[2]);
            $this->assertCommandSucceeded("ACKNOWLEDGE_SVC_PROBLEM;hostC;svc;0;0;0;I can;sends teh ack",$result[3]);

        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether a single host comment is correctly serialized and send to the command pipe
     *
     * @throws \Exception|Exception
     */
    public function testAddHostComment()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->addComment(array((object) array("host_name" => "hostA")),
                new Comment("Autor","Comment")
            );
            $this->assertCommandSucceeded("ADD_HOST_COMMENT;hostA;0;Autor;Comment");
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether removing all hostcomments is correctly serialized and send to the command pipe
     *
     * @throws \Exception|Exception
     */
    public function testRemoveAllHostComment()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->removeComment(array(
                (object) array(
                    "host_name" => "test"
                )
            ));
            $this->assertCommandSucceeded("DEL_ALL_HOST_COMMENTS;test");
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether removing a single host comment is correctly serialized and send to the command pipe
     *
     * @throws \Exception|Exception
     */
    public function testRemoveSpecificComment()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->removeComment(array((object) array("comment_id"=>34,"host_name"=>"test")));
            $this->assertCommandSucceeded("DEL_HOST_COMMENT;34");
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether a multiple reschedules for services and hosts are correctly serialized and send to the commandpipe
     *
     * @throws \Exception|Exception
     */
    public function testScheduleChecks()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->getTransport()->setOpenMode("a"); // append so we have multiple results
            $t = time();
            // normal reschedule
            $pipe->scheduleCheck(array(
                (object) array("host_name"=>"test"),
                (object) array("host_name"=>"test","service_description"=>"svc1")
            ),$t);
            // forced
            $pipe->scheduleForcedCheck(array(
                (object) array("host_name"=>"test"),
                (object) array("host_name"=>"test","service_description"=>"svc1")
            ),$t);
            // forced, recursive
            $pipe->scheduleForcedCheck(array(
                (object) array("host_name"=>"test"),
            ),$t,true);

            $result = explode("\n",file_get_contents($this->getPipeName()));
            $this->assertCount(6,$result, "Asserting a correct number of commands being written to the commandpipe");

            $this->assertCommandSucceeded("SCHEDULE_HOST_CHECK;test;".$t,$result[0]);
            $this->assertCommandSucceeded("SCHEDULE_SVC_CHECK;test;svc1;".$t,$result[1]);
            $this->assertCommandSucceeded("SCHEDULE_FORCED_HOST_CHECK;test;".$t,$result[2]);
            $this->assertCommandSucceeded("SCHEDULE_FORCED_SVC_CHECK;test;svc1;".$t,$result[3]);
            $this->assertCommandSucceeded("SCHEDULE_FORCED_HOST_SVC_CHECKS;test;".$t,$result[4]);


        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether modifying monitoringflags of a host and service is correctly serialized and send to the command pipe
     *
     * @throws \Exception|Exception
     */
    public function testObjectStateModifications()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->getTransport()->setOpenMode("a");
            $pipe->setMonitoringProperties(array(
                    (object) array(
                        "host_name" => "Testhost"
                    ),
                    (object) array(
                        "host_name" => "host",
                        "service_description" => "svc"
                    )
                ), new MONFLAG(array(
                    MONFLAG::ACTIVE => MONFLAG::STATE_DISABLE,
                    MONFLAG::PASSIVE => MONFLAG::STATE_ENABLE,
                    MONFLAG::NOTIFICATIONS => MONFLAG::STATE_DISABLE,
                    MONFLAG::EVENTHANDLER => MONFLAG::STATE_ENABLE,
                    MONFLAG::FLAPPING => MONFLAG::STATE_DISABLE,
                    MONFLAG::FRESHNESS => MONFLAG::STATE_ENABLE,
                ))
            );

            $result = explode("\n",file_get_contents($this->getPipeName()));
            array_pop($result); // remove empty last line
            $this->assertCount(12,$result, "Asserting a correct number of commands being written to the commandpipe");
            foreach ($result as $command) {
                $this->assertCommandSucceeded(false,$command);
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
     * @throws \Exception|Exception
     */
    public function testGlobalNotificationTrigger()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->enableGlobalNotifications();
            $this->assertCommandSucceeded("ENABLE_NOTIFICATIONS;");
            $pipe->disableGlobalNotifications();
            $this->assertCommandSucceeded("DISABLE_NOTIFICATIONS;");
        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether host and servicedowntimes are correctly scheduled
     *
     * @throws \Exception|Exception
     */
    public function testScheduleDowntime()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $downtime = new Downtime(25,26,new Comment("me","test"));
            $pipe->scheduleDowntime(array(
                (object) array(
                    "host_name" => "Testhost"
                )
            ),$downtime);
            $this->assertCommandSucceeded("SCHEDULE_HOST_DOWNTIME;Testhost;25;26;0;0;0;me;test");

            $pipe->scheduleDowntime(array(
                (object) array(
                    "host_name" => "Testhost",
                    "service_description" => "svc"
                )
            ),$downtime);
            $this->assertCommandSucceeded("SCHEDULE_SVC_DOWNTIME;Testhost;svc;25;26;0;0;0;me;test");

        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether the removal of downtimes is correctly serialized and send to the commandpipe for hosts and services
     *
     * @throws \Exception|Exception
     */
    public function testRemoveDowntime()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $pipe->getTransport()->setOpenMode("a");
            $pipe->removeDowntime(array(
                (object) array(
                    "host_name" => "Testhost"
                ),
                (object) array(
                    "host_name" => "host",
                    "service_description" => "svc"
                ),
                (object) array(
                    "host_name" => "host",
                    "service_description" => "svc",
                    "downtime_id" => 123
                )
            ));
            $result = explode("\n",file_get_contents($this->getPipeName()));
            array_pop($result); // remove empty last line
            $this->assertCount(3,$result, "Asserting a correct number of commands being written to the commandpipe");
            $this->assertCommandSucceeded("DEL_DOWNTIME_BY_HOST_NAME;Testhost",$result[0]);
            $this->assertCommandSucceeded("DEL_DOWNTIME_BY_HOST_NAME;host;svc",$result[1]);
            $this->assertCommandSucceeded("DEL_SVC_DOWNTIME;123",$result[2]);

        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether custom  servicenotifications are correctly send to the commandpipe without options
     *
     * @throws \Exception
     */
    public function testSendCustomServiceNotification()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $comment = new Comment("author", "commenttext");
            $pipe->sendCustomNotification(array(
                (object) array(
                    "host_name" => "host1",
                    "service_description" => "service1"
                )
            ), $comment);
            $this->assertCommandSucceeded(
                "SEND_CUSTOM_SVC_NOTIFICATION;host1;service1;0;author;commenttext"
            );
        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test whether custom hostnotifications are correctly send to the commandpipe with a varlist of options
     *
     * @throws \Exception
     */
    public function testSendCustomHostNotificationWithOptions()
    {
        $pipe = $this->getLocalTestPipe();
        try {
            $comment = new Comment('author', 'commenttext');
            $pipe->sendCustomNotification(array(
                (object) array(
                    'host_name' => 'host'
                )
            ), $comment, Commandpipe::NOTIFY_FORCED, Commandpipe::NOTIFY_BROADCAST, Commandpipe::NOTIFY_INCREMENT);

            $this->assertCommandSucceeded(
                'SEND_CUSTOM_HOST_NOTIFICATION;host;7;author;commenttext'
            );
        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    /**
     * Test sending of commands via SSH (currently disabled)
     *
     * @throws \Exception|Exception
     */
    public function testSSHCommands()
    {
        $this->markTestSkipped("This test assumes running in a vagrant VM with key-auth");

        if (!is_dir("/vagrant")) {
        }
        $pipe = $this->getSSHTestPipe();
        try {
            $ack = new Acknowledgement(new Comment("I can","sends teh ack"));
            $pipe->acknowledge(array(
                (object) array(
                    "host_name" => "hostA"
                )
            ),$ack);
            $this->assertCommandSucceeded("ACKNOWLEDGE_HOST_PROBLEM;hostA;0;0;0;I can;sends teh ack");
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }
}
