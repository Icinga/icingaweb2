<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Forms\Config\UserBackend;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Data\ConfigObject;
use Icinga\Test\BaseTestCase;
use Icinga\Forms\Config\UserBackend\LdapBackendForm;
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
        $ldapUserBackendMock = Mockery::mock('overload:Icinga\Authentication\User\LdapUserBackend');
        $ldapUserBackendMock->shouldReceive('assertAuthenticationPossible')->andReturnNull();
        $this->setUpUserBackendMock($ldapUserBackendMock);

        // Passing array(null) is required to make Mockery call the constructor...
        $form = Mockery::mock('Icinga\Forms\Config\UserBackend\LdapBackendForm[getView]', array(null));
        $form->shouldReceive('getView->escape')
            ->with(Mockery::type('string'))
            ->andReturnUsing(function ($s) { return $s; });
        $form->setTokenDisabled();
        $form->setResources(array('test_ldap_backend'));
        $form->populate(array('resource' => 'test_ldap_backend'));

        $this->assertTrue(
            LdapBackendForm::isValidUserBackend($form),
            'LdapBackendForm claims that a valid user backend with users is not valid'
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInvalidBackendIsNotValid()
    {
        $ldapUserBackendMock = Mockery::mock('overload:Icinga\Authentication\User\LdapUserBackend');
        $ldapUserBackendMock->shouldReceive('assertAuthenticationPossible')->andThrow(new AuthenticationException);
        $this->setUpUserBackendMock($ldapUserBackendMock);

        // Passing array(null) is required to make Mockery call the constructor...
        $form = Mockery::mock('Icinga\Forms\Config\UserBackend\LdapBackendForm[getView]', array(null));
        $form->shouldReceive('getView->escape')
            ->with(Mockery::type('string'))
            ->andReturnUsing(function ($s) { return $s; });
        $form->setTokenDisabled();
        $form->setResources(array('test_ldap_backend'));
        $form->populate(array('resource' => 'test_ldap_backend'));

        $this->assertFalse(
            LdapBackendForm::isValidUserBackend($form),
            'LdapBackendForm claims that an invalid user backend without users is valid'
        );
    }

    protected function setUpUserBackendMock($ldapUserBackendMock)
    {
        Mockery::mock('alias:Icinga\Authentication\User\UserBackend')
            ->shouldReceive('create')
            ->andReturn($ldapUserBackendMock);
    }
}
