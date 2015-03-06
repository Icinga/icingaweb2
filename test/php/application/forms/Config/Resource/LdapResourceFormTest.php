<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Forms\Config\Resource;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\Forms\Config\Resource\LdapResourceForm;

class LdapResourceFormTest extends BaseTestCase
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
    public function testValidLdapResourceIsValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('testCredentials')->once()->andReturn(true)->getMock()
        );

        $form = Mockery::mock('Icinga\Forms\Config\Resource\LdapResourceForm[getView]');
        $form->shouldReceive('getView->escape')
            ->with(Mockery::type('string'))
            ->andReturnUsing(function ($s) { return $s; });
        $form->setTokenDisabled();

        $this->assertTrue(
            LdapResourceForm::isValidResource($form->create()),
            'ResourceForm claims that a valid ldap resource is not valid'
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInvalidLdapResourceIsNotValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('testCredentials')->once()->andThrow('\Exception')->getMock()
        );

        $form = Mockery::mock('Icinga\Forms\Config\Resource\LdapResourceForm[getView]');
        $form->shouldReceive('getView->escape')
            ->with(Mockery::type('string'))
            ->andReturnUsing(function ($s) { return $s; });
        $form->setTokenDisabled();

        $this->assertFalse(
            LdapResourceForm::isValidResource($form->create()),
            'ResourceForm claims that an invalid ldap resource is valid'
        );
    }

    protected function setUpResourceFactoryMock($resourceMock)
    {
        Mockery::mock('alias:Icinga\Data\ResourceFactory')
            ->shouldReceive('createResource')
            ->with(Mockery::type('Icinga\Data\ConfigObject'))
            ->andReturn($resourceMock);
    }
}
