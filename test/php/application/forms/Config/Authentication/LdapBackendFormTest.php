<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Forms\Config\Authentication;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Data\ConfigObject;
use Icinga\Test\BaseTestCase;
use Icinga\Forms\Config\Authentication\LdapBackendForm;
use Icinga\Exception\AuthenticationException;

class LdapBackendFormTest extends BaseTestCase
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
    public function testValidBackendIsValid()
    {
        $this->setUpResourceFactoryMock();
        Mockery::mock('overload:Icinga\Authentication\Backend\LdapUserBackend')
            ->shouldReceive('assertAuthenticationPossible')->andReturnNull();

        $form = Mockery::mock('Icinga\Forms\Config\Authentication\LdapBackendForm[getView]');
        $form->shouldReceive('getView->escape')
            ->with(Mockery::type('string'))
            ->andReturnUsing(function ($s) { return $s; });
        $form->setTokenDisabled();
        $form->setResources(array('test_ldap_backend'));
        $form->populate(array('resource' => 'test_ldap_backend'));

        $this->assertTrue(
            LdapBackendForm::isValidAuthenticationBackend($form),
            'LdapBackendForm claims that a valid authentication backend with users is not valid'
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInvalidBackendIsNotValid()
    {
        $this->setUpResourceFactoryMock();
        Mockery::mock('overload:Icinga\Authentication\Backend\LdapUserBackend')
            ->shouldReceive('assertAuthenticationPossible')->andThrow(new AuthenticationException);

        $form = Mockery::mock('Icinga\Forms\Config\Authentication\LdapBackendForm[getView]');
        $form->shouldReceive('getView->escape')
            ->with(Mockery::type('string'))
            ->andReturnUsing(function ($s) { return $s; });
        $form->setTokenDisabled();
        $form->setResources(array('test_ldap_backend'));
        $form->populate(array('resource' => 'test_ldap_backend'));

        $this->assertFalse(
            LdapBackendForm::isValidAuthenticationBackend($form),
            'LdapBackendForm claims that an invalid authentication backend without users is valid'
        );
    }

    protected function setUpResourceFactoryMock()
    {
        Mockery::mock('alias:Icinga\Data\ResourceFactory')
            ->shouldReceive('createResource')
            ->andReturn(Mockery::mock('Icinga\Protocol\Ldap\Connection'))
            ->shouldReceive('getResourceConfig')
            ->andReturn(new ConfigObject());
    }
}
