<?php

namespace Tests\Icinga\Module\Monitoring\Regression;

use Icinga\Data\ResourceFactory;
use Icinga\Module\Monitoring\Backend;
use Icinga\Test\BaseTestCase;

class Bug7043 extends BaseTestCase
{
    public function testBackendDefaultName()
    {
        $config = new \Zend_Config(array(
            'ido' => array(
                'type' => 'db',
                'db'        => 'mysql',
                'host'      => 'localhost',
                'port'      => '3306',
                'password'  => 'icinga',
                'username'  => 'icinga',
                'dbname'    => 'icinga'
            )
        ));

        ResourceFactory::setConfig($config);

        $defaultBackend = Backend::createBackend();

        $this->assertNotNull($defaultBackend->getName(), 'Default backend has a name property set');
    }
}