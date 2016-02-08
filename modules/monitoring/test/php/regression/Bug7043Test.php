<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Module\Monitoring\Regression;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../../test/php/bootstrap.php');

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Module\Monitoring\Backend;
use Icinga\Test\BaseTestCase;
use Mockery;


class ConfigWithSetModuleConfig extends Config
{
    public static function setModuleConfig($moduleName, $configName, $config)
    {
        static::$modules[$moduleName][$configName] = $config;
    }
}


class Bug7043Test extends BaseTestCase
{
    public function tearDown()
    {
        parent::tearDown();
        Mockery::close(); // Necessary because some tests run in a separate process
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBackendDefaultName()
    {
        Mockery::mock('alias:Icinga\Data\ResourceFactory')
            ->shouldReceive('create')
            ->andReturn(
                Mockery::mock('Icinga\Data\Db\DbConnection')
                    ->shouldReceive('getDbType')
                    ->andReturn('mysql')
                    ->shouldReceive('setTablePrefix')
                    ->getMock()
            );

        ConfigWithSetModuleConfig::setModuleConfig('monitoring', 'backends', new ConfigObject(array(
            'backendName' => array(
                'type'      => 'ido',
                'resource'  => 'ido'
            )
        )));

        $defaultBackend = Backend::createBackend();

        $this->assertEquals('backendName', $defaultBackend->getName(), 'Default backend has name set');
    }
}
