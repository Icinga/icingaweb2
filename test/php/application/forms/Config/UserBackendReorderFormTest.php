<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Forms\Config;

use Icinga\Test\BaseTestCase;
use Icinga\Application\Config;
use Icinga\Forms\Config\UserBackendConfigForm;
use Icinga\Forms\Config\UserBackendReorderForm;

class UserBackendConfigFormWithoutSave extends UserBackendConfigForm
{
    public static $newConfig;

    public function save()
    {
        self::$newConfig = $this->config;
        return false;
    }
}

class UserBackendReorderFormProvidingConfigFormWithoutSave extends UserBackendReorderForm
{
    public function getConfigForm()
    {
        $form = new UserBackendConfigFormWithoutSave();
        $form->setIniConfig($this->config);
        return $form;
    }
}

class AuthenticationBackendReorderFormTest extends BaseTestCase
{
    public function testMoveBackend()
    {
        $config = Config::fromArray(
            array(
                'test1' => '',
                'test2' => '',
                'test3' => ''
            )
        );

        $this->getRequestMock()->shouldReceive('getMethod')->andReturn('POST')
            ->shouldReceive('isPost')->andReturn(true)
            ->shouldReceive('getPost')->andReturn(array('backend_newpos' => 'test3|1'));

        $form = new UserBackendReorderFormProvidingConfigFormWithoutSave();
        $form->setIniConfig($config);
        $form->setTokenDisabled();
        $form->setUidDisabled();
        $form->handleRequest();

        $this->assertEquals(
            array('test1', 'test3', 'test2'),
            UserBackendConfigFormWithoutSave::$newConfig->keys(),
            'Moving elements with UserBackendReorderForm does not seem to properly work'
        );
    }
}
