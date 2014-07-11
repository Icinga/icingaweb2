<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config\Authentication;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\Form\Config\Authentication\LdapBackendForm;
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
            ->shouldReceive('assertAuthenticationPossible')->andReturn(null);

        $form = new LdapBackendForm();
        $form->setBackendName('test');
        $form->setResources(array('test_ldap_backend' => null));
        $form->create();
        $form->populate(array('backend_test_resource' => 'test_ldap_backend'));

        $this->assertTrue(
            $form->isValidAuthenticationBackend(),
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

        $form = new LdapBackendForm();
        $form->setBackendName('test');
        $form->setResources(array('test_ldap_backend' => null));
        $form->create();
        $form->populate(array('backend_test_resource' => 'test_ldap_backend'));

        $this->assertFalse(
            $form->isValidAuthenticationBackend(),
            'LdapBackendForm claims that an invalid authentication backend without users is valid'
        );
    }

    protected function setUpResourceFactoryMock()
    {
        Mockery::mock('alias:Icinga\Data\ResourceFactory')
            ->shouldReceive('ldapAvailable')
            ->andReturn(true)
            ->shouldReceive('getResourceConfig')
            ->andReturn(new \Zend_Config(array()))
            ->shouldReceive('createResource')
            ->with(Mockery::type('\Zend_Config'))
            ->andReturn(Mockery::mock('Icinga\Protocol\Ldap\Connection'));
    }
}
