<?php
namespace Tests\Icinga\Protocol\Commandpipe;

use Icinga\Protocol\Commandpipe\Comment as Comment;
use Icinga\Protocol\Commandpipe\Acknowledgement as Acknowledgement;
use Icinga\Protocol\Commandpipe\Downtime as Downtime;
use Icinga\Protocol\Commandpipe\Commandpipe as Commandpipe;
use \Icinga\Protocol\Commandpipe\PropertyModifier as MONFLAG;

require_once("Zend/Config.php");
require_once("Zend/Log.php");
require_once("../../library/Icinga/Application/Logger.php");

require_once("../../library/Icinga/Protocol/Commandpipe/IComment.php");
require_once("../../library/Icinga/Protocol/Commandpipe/Comment.php");
require_once("../../library/Icinga/Protocol/Commandpipe/CommandPipe.php");
require_once("../../library/Icinga/Protocol/Commandpipe/Acknowledgement.php");
require_once("../../library/Icinga/Protocol/Commandpipe/Downtime.php");
require_once("../../library/Icinga/Protocol/Commandpipe/PropertyModifier.php");
require_once("../../library/Icinga/Protocol/Commandpipe/Exception/InvalidCommandException.php");

if(!defined("EXTCMD_TEST_BIN"))
    define("EXTCMD_TEST_BIN", "./bin/extcmd_test");

class CommandPipeTest extends \PHPUnit_Framework_TestCase
{

    public function getPipeName()
    {
        return sys_get_temp_dir()."/icinga_test_pipe";
    }
    private function getTestPipe()
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

    private function cleanup() {
        if(file_exists($this->getPipeName()))
            unlink($this->getPipeName());
    }

    private function assertCommandSucceeded($expectedString = false,$command = false) {
        $resultCode = null;
        $resultArr = array();
        $receivedCmd = exec(EXTCMD_TEST_BIN." ".escapeshellarg($command ? $command : file_get_contents($this->getPipeName())),$resultArr,$resultCode);
        $this->assertEquals(0,$resultCode,"Submit of external command returned error : ".$receivedCmd);
        if (!$expectedString)
           return;
        $this->assertEquals(
            $expectedString,
            $receivedCmd
        );
    }
    public function testAcknowledgeSingleHost()
    {
        $pipe = $this->getTestPipe();
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

    public function testAcknowledgeMultipleObjects()
    {
        $pipe = $this->getTestPipe();
        try {
            $ack = new Comment("I can","sends teh ack");
            $pipe->fopen_mode = "a";
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

            $this->assertCount(5,$result);

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

    public function testAddHostComment()
    {
        $pipe = $this->getTestPipe();
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

    public function testRemoveAllHostComment()
    {
        $pipe = $this->getTestPipe();
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

    public function testRemoveSpecificComment()
    {
        $pipe = $this->getTestPipe();
        try {
            $pipe->removeComment(array((object) array("comment_id"=>34,"host_name"=>"test")));
            $this->assertCommandSucceeded("DEL_HOST_COMMENT;34");
        } catch(Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    public function testScheduleChecks()
    {
        $pipe = $this->getTestPipe();
        try {
            $pipe->fopen_mode = "a"; // append so we have multiple results
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
            $this->assertCount(6,$result);

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

    public function testObjectStateModifications()
    {
        $pipe = $this->getTestPipe();
        try {
            $pipe->fopen_mode = "a";
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
            $this->assertCount(12,$result);
            foreach ($result as $command) {
                $this->assertCommandSucceeded(false,$command);
            }

        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

    public function testGlobalNotificationTrigger()
    {
        $pipe = $this->getTestPipe();
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

    public function testScheduleDowntime()
    {
        $pipe = $this->getTestPipe();
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

    public function testRemoveDowntime()
    {
        $pipe = $this->getTestPipe();
        try {
            $pipe->fopen_mode = "a";
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
            $this->assertCount(3,$result);
            $this->assertCommandSucceeded("DEL_DOWNTIME_BY_HOST_NAME;Testhost",$result[0]);
            $this->assertCommandSucceeded("DEL_DOWNTIME_BY_HOST_NAME;host;svc",$result[1]);
            $this->assertCommandSucceeded("DEL_SVC_DOWNTIME;123",$result[2]);

        } catch (Exception $e) {
            $this->cleanup();
            throw $e;
        }
        $this->cleanup();
    }

}
