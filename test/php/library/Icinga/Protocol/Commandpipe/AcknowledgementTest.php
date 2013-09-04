<?php

namespace Tests\Icinga\Protocol\Commandpipe;

use Icinga\Protocol\Commandpipe\Comment;
use Monitoring\Command\AcknowledgeCommand;

require_once("../../library/Icinga/Protocol/Commandpipe/IComment.php");
require_once("../../library/Icinga/Protocol/Commandpipe/Comment.php");
require_once("../../library/Icinga/Protocol/Commandpipe/CommandType.php");
require_once("../../library/Icinga/Protocol/Commandpipe/CommandPipe.php");
require_once('../../modules/monitoring/library/Monitoring/Command/BaseCommand.php');
require_once('../../modules/monitoring/library/Monitoring/Command/AcknowledgeCommand.php');

class AcknowledgementTest extends \PHPUnit_Framework_TestCase
{
    public function testAcknowledgeHostMessage()
    {
        $ack = new AcknowledgeCommand(new Comment("author", "commentdata"));
        $this->assertEquals("ACKNOWLEDGE_HOST_PROBLEM;foo;0;0;0;author;commentdata", $ack->getHostCommand('foo'));

        $ack->setExpire(1000);
        $this->assertEquals("ACKNOWLEDGE_HOST_PROBLEM_EXPIRE;bar;0;0;0;1000;author;commentdata", $ack->getHostCommand('bar'));
    }

    public function testAcknowledgeServiceMessage()
    {
        $ack = new AcknowledgeCommand(new Comment("author","commentdata"));
        $this->assertEquals("ACKNOWLEDGE_SVC_PROBLEM;foo;bar;0;0;0;author;commentdata", $ack->getServiceCommand('foo', 'bar'));

        $ack->setExpire(1000);
        $this->assertEquals("ACKNOWLEDGE_SVC_PROBLEM_EXPIRE;bar;foo;0;0;0;1000;author;commentdata", $ack->getServiceCommand('bar', 'foo'));
    }
}
