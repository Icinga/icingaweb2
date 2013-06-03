<?php

namespace Tests\Icinga\Protocol\Commandpipe;

use Icinga\Protocol\Commandpipe\Comment as Comment;
use Icinga\Protocol\Commandpipe\Commandpipe as Commandpipe;

require_once("../../library/Icinga/Protocol/Commandpipe/IComment.php");
require_once("../../library/Icinga/Protocol/Commandpipe/Comment.php");
require_once("../../library/Icinga/Protocol/Commandpipe/CommandPipe.php");
require_once("../../library/Icinga/Protocol/Commandpipe/Acknowledgement.php");
require_once("../../library/Icinga/Protocol/Commandpipe/Exception/InvalidCommandException.php");

class AcknowledgementTest extends \PHPUnit_Framework_TestCase
{


    public function testAcknowledgeHostMessage()
    {
        $ack = new \Icinga\Protocol\Commandpipe\Acknowledgement(new Comment("author","commentdata"),false);
        $this->assertEquals("ACKNOWLEDGE_HOST_PROBLEM;%s;0;0;0;author;commentdata",$ack->getFormatString(CommandPipe::TYPE_HOST));

        $ack->setExpireTime(1000);
        $this->assertEquals("ACKNOWLEDGE_HOST_PROBLEM_EXPIRE;%s;0;0;0;1000;author;commentdata",$ack->getFormatString(CommandPipe::TYPE_HOST));
    }

    public function testAcknowledgeServiceMessage()
    {
        $ack = new \Icinga\Protocol\Commandpipe\Acknowledgement(new Comment("author","commentdata"),false);
        $this->assertEquals("ACKNOWLEDGE_SVC_PROBLEM;%s;%s;0;0;0;author;commentdata",$ack->getFormatString(CommandPipe::TYPE_SERVICE));

        $ack->setExpireTime(1000);
        $this->assertEquals("ACKNOWLEDGE_SVC_PROBLEM_EXPIRE;%s;%s;0;0;0;1000;author;commentdata",$ack->getFormatString(CommandPipe::TYPE_SERVICE));
    }

    /**
     * @expectedException \Icinga\Protocol\Commandpipe\Exception\InvalidCommandException
     */
    public function testInvalidServicegroupAcknowledgement()
    {
        $ack = new \Icinga\Protocol\Commandpipe\Acknowledgement(new Comment("author","commentdata"),false);
        $ack->getFormatString(CommandPipe::TYPE_SERVICEGROUP);

    }

    /**
     * @expectedException \Icinga\Protocol\Commandpipe\Exception\InvalidCommandException
     */
    public function testInvalidHostgroupAcknowledgement()
    {
        $ack = new \Icinga\Protocol\Commandpipe\Acknowledgement(new Comment("author","commentdata"),false);
        $ack->getFormatString(CommandPipe::TYPE_HOSTGROUP);

    }
}
