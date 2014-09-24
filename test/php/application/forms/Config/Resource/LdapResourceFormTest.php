<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config\Resource;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\Form\Config\Resource\LdapResourceForm;

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
            Mockery::mock()->shouldReceive('connect')->getMock()
        );

        $form = new LdapResourceForm();

        $this->assertTrue(
            $form->isValidResource($form),
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
            Mockery::mock()->shouldReceive('connect')->once()->andThrow('\Exception')->getMock()
        );

        $form = new LdapResourceForm();

        $this->assertFalse(
            $form->isValidResource($form),
            'ResourceForm claims that an invalid ldap resource is valid'
        );
    }

    protected function setUpResourceFactoryMock($resourceMock)
    {
        Mockery::mock('alias:Icinga\Data\ResourceFactory')
            ->shouldReceive('createResource')
            ->with(Mockery::type('\Zend_Config'))
            ->andReturn($resourceMock);
    }
}
