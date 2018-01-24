<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Test;

use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\Module\Monitoring\Command\Renderer\IcingaApiCommandRenderer;
use Icinga\Module\Monitoring\Command\Renderer\IcingaCommandFileCommandRenderer;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Test\BaseTestCase;

abstract class CommandTest extends BaseTestCase
{
    /** @var DummyBackend */
    protected $backend;

    /** @var IcingaCommand */
    protected $command;

    protected static $namespace = 'Icinga\\Module\\Monitoring\\Command\\Object';

    /**
     * Prepare DummyBackend and the basic command to test with
     */
    public function setUp()
    {
        $this->backend = new DummyBackend;

        $this->command = $this->getRealClass();
    }

    /**
     * Create a new command from the matching class name
     *
     * @return IcingaCommand
     */
    protected function getRealClass()
    {
        $class = static::$namespace . '\\' . $this->getClassName();
        return new $class;
    }

    /**
     * Get the command class name for this test
     *
     * @return IcingaCommand
     */
    protected function getClassName()
    {
        $nsParts = explode('\\', get_called_class());
        return substr_replace(end($nsParts), '', -4);  // Remove 'Test' Suffix
    }

    /**
     * Build a basic dummy Host for testing
     *
     * @param string $hostname
     *
     * @return Host
     */
    protected function buildHost($hostname = 'localhost')
    {
        $host = new Host($this->backend, $hostname);
        $host->instance_name = 'test';

        return $host;
    }

    /**
     * Build a basic dummy Service for testing
     *
     * @param string $hostname
     * @param string $servicename
     *
     * @return Service
     */
    protected function buildService($hostname = 'localhost', $servicename = 'ping')
    {
        $service = new Service($this->backend, $hostname, $servicename);
        $service->instance_name = 'test';

        return $service;
    }

    /**
     * Render the command with IcingaCommandFileCommandRenderer
     *
     * @param IcingaCommand $command
     *
     * @return string
     */
    protected function renderFile(IcingaCommand $command = null)
    {
        if ($command === null) {
            $command = $this->command;
        }
        $renderer = new IcingaCommandFileCommandRenderer();
        return $renderer->render($command);
    }

    protected function renderApi(IcingaCommand $command = null)
    {
        if ($command === null) {
            $command = $this->command;
        }
        $renderer = new IcingaApiCommandRenderer();
        return $renderer->render($command);
    }
}
