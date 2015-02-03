<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Tests\Icinga\Forms\Config;

use Icinga\Test\BaseTestCase;
use Icinga\Application\Config;
use Icinga\Forms\Config\AuthenticationBackendConfigForm;
use Icinga\Forms\Config\AuthenticationBackendReorderForm;

class AuthenticationBackendConfigFormWithoutSave extends AuthenticationBackendConfigForm
{
    public static $newConfig;

    public function save()
    {
        self::$newConfig = $this->config;
        return false;
    }
}

class AuthenticationBackendReorderFormProvidingConfigFormWithoutSave extends AuthenticationBackendReorderForm
{
    public function getConfigForm()
    {
        $form = new AuthenticationBackendConfigFormWithoutSave();
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

        $form = new AuthenticationBackendReorderFormProvidingConfigFormWithoutSave();
        $form->setIniConfig($config);
        $form->setTokenDisabled();
        $form->setUidDisabled();
        $form->handleRequest();

        $this->assertEquals(
            array('test1', 'test3', 'test2'),
            AuthenticationBackendConfigFormWithoutSave::$newConfig->keys(),
            'Moving elements with AuthenticationBackendReorderForm does not seem to properly work'
        );
    }
}
