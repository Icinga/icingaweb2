<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Modules\Monitoring\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteCommentCommand;
use Icinga\Module\Monitoring\Test\CommandTest;

class DeleteCommentCommandTest extends CommandTest
{
    /** @var  DeleteCommentCommand */
    protected $command;

    public function testHost()
    {
        $this->command->setCommentId(1234)
            ->setCommentName('comment-1234')
            ->setObject($this->buildHost());

        $this->assertRegExp('~ DEL_HOST_COMMENT;1234$~', $this->renderFile());

        $apiCommand = $this->renderApi();
        $data = $apiCommand->getData();
        $this->assertEquals('comment-1234', $data['comment']);
        $this->assertEquals('actions/remove-comment', $apiCommand->getEndpoint());
    }

    public function testService()
    {
        $this->command->setCommentId(1234)
            ->setCommentName('comment-1234')
            ->setObject($this->buildService());

        $this->assertRegExp('~ DEL_SVC_COMMENT;1234$~', $this->renderFile());

        $apiCommand = $this->renderApi();
        $data = $apiCommand->getData();
        $this->assertEquals('comment-1234', $data['comment']);
        $this->assertEquals('actions/remove-comment', $apiCommand->getEndpoint());
    }
}
