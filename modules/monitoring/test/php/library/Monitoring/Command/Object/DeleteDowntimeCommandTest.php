<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Modules\Monitoring\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteDowntimeCommand;
use Icinga\Module\Monitoring\Test\CommandTest;

class DeleteDowntimeCommandTest extends CommandTest
{
    /** @var  DeleteDowntimeCommand */
    protected $command;

    public function testHost()
    {
        $this->command->setDowntimeId(1234)
            ->setDowntimeName('downtime-1234')
            ->setObject($this->buildHost());

        $this->assertRegExp('~ DEL_HOST_DOWNTIME;1234$~', $this->renderFile());

        $apiCommand = $this->renderApi();
        $data = $apiCommand->getData();
        $this->assertEquals('downtime-1234', $data['downtime']);
        $this->assertEquals('actions/remove-downtime', $apiCommand->getEndpoint());
    }

    public function testService()
    {
        $this->command->setDowntimeId(1234)
            ->setDowntimeName('downtime-1234')
            ->setObject($this->buildService());

        $this->assertRegExp('~ DEL_SVC_DOWNTIME;1234$~', $this->renderFile());

        $apiCommand = $this->renderApi();
        $data = $apiCommand->getData();
        $this->assertEquals('downtime-1234', $data['downtime']);
        $this->assertEquals('actions/remove-downtime', $apiCommand->getEndpoint());
    }
}
