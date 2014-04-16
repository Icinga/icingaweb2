<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config\Authentication;

use \Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\Form\Config\Authentication\LdapBackendForm;

class LdapBackendFormTest extends BaseTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testValidBackendIsValid()
    {
        Mockery::mock('alias:Icinga\Authentication\UserBackend')
            ->shouldReceive('create')->with('test', Mockery::type('\Zend_Config'))->andReturnUsing(
                function () { return Mockery::mock(array('count' => 1)); }
        );

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
     */
    public function testInvalidBackendIsNotValid()
    {
        Mockery::mock('alias:Icinga\Authentication\UserBackend')
            ->shouldReceive('create')->with('test', Mockery::type('\Zend_Config'))->andReturnUsing(
                function () { return Mockery::mock(array('count' => 0)); }
        );

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
}
