<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config\Resource;

use Mockery;
use Zend_Config;
use Icinga\Test\BaseTestCase;
use Icinga\Form\Config\Resource\ResourceForm;

class TestResourceForm extends ResourceForm
{
    public $is_valid;

    public function isValidResource()
    {
        return $this->is_valid;
    }
}

class ResourceFormTest extends BaseTestCase
{
    public function tearDown()
    {
        parent::tearDown();
        Mockery::close(); // Necessary because some tests run in a separate process
    }

    public function testIsForceCreationCheckboxBeingAdded()
    {
        $form = new TestResourceForm();
        $form->is_valid = false;

        $this->assertFalse($form->isValid(array()));
        $this->assertNotNull(
            $form->getElement('resource_force_creation'),
            'Checkbox to force the creation of a resource is not being added though the resource is invalid'
        );
    }

    public function testIsForceCreationCheckboxNotBeingAdded()
    {
        $form = new TestResourceForm();
        $form->is_valid = true;

        $this->assertTrue($form->isValid(array()));
        $this->assertNull(
            $form->getElement('resource_force_creation'),
            'Checkbox to force the creation of a resource is being added though the resource is valid'
        );
    }

    public function testIsTheFormValidIfForceCreationTrue()
    {
        $form = new TestResourceForm();
        $form->is_valid = false;

        $this->assertTrue(
            $form->isValid(array('resource_force_creation' => 1)),
            'ResourceForm with invalid resource is not valid though force creation is set'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testValidDbResourceIsValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('getConnection')->atMost()->twice()->andReturn(Mockery::self())->getMock()
        );
        $form = $this->buildResourceForm(new Zend_Config(array('type' => 'db')));

        $this->assertTrue(
            $form->isValidResource(),
            'ResourceForm claims that a valid db resource is not valid'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidDbResourceIsNotValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('getConnection')->once()->andThrow('\Exception')->getMock()
        );
        $form = $this->buildResourceForm(new Zend_Config(array('type' => 'db')));

        $this->assertFalse(
            $form->isValidResource(),
            'ResourceForm claims that an invalid db resource is valid'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testValidLdapResourceIsValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('connect')->getMock()
        );
        $form = $this->buildResourceForm(new Zend_Config(array('type' => 'ldap')));

        $this->assertTrue(
            $form->isValidResource(),
            'ResourceForm claims that a valid ldap resource is not valid'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidLdapResourceIsNotValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('connect')->once()->andThrow('\Exception')->getMock()
        );
        $form = $this->buildResourceForm(new Zend_Config(array('type' => 'ldap')));

        $this->assertFalse(
            $form->isValidResource(),
            'ResourceForm claims that an invalid ldap resource is valid'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testValidLivestatusResourceIsValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('connect')->andReturn(Mockery::self())
                ->shouldReceive('disconnect')->getMock()
        );
        $form = $this->buildResourceForm(new Zend_Config(array('type' => 'livestatus')));

        $this->assertTrue(
            $form->isValidResource(),
            'ResourceForm claims that a valid livestatus resource is not valid'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidLivestatusResourceIsNotValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('connect')->once()->andThrow('\Exception')->getMock()
        );
        $form = $this->buildResourceForm(new Zend_Config(array('type' => 'livestatus')));

        $this->assertFalse(
            $form->isValidResource(),
            'ResourceForm claims that an invalid livestatus resource is valid'
        );
    }

    public function testValidFileResourceIsValid()
    {
        $form = $this->buildResourceForm(
            new Zend_Config(
                array(
                    'type'      => 'file',
                    'filename'  => BaseTestCase::$testDir . '/res/status/icinga.status.dat'
                )
            )
        );

        $this->assertTrue(
            $form->isValidResource(),
            'ResourceForm claims that a valid file resource is not valid'
        );
    }

    public function testInvalidFileResourceIsNotValid()
    {
        $form = $this->buildResourceForm(
            new Zend_Config(
                array(
                    'type'      => 'file',
                    'filename'  => 'not_existing'
                )
            )
        );

        $this->assertFalse(
            $form->isValidResource(),
            'ResourceForm claims that an invalid file resource is valid'
        );
    }

    public function testValidStatusdatResourceIsValid()
    {
        $form = $this->buildResourceForm(
            new Zend_Config(
                array(
                    'type'          => 'statusdat',
                    'status_file'   => BaseTestCase::$testDir . '/res/status/icinga.status.dat',
                    'object_file'   => BaseTestCase::$testDir . '/res/status/icinga.objects.cache',
                )
            )
        );

        $this->assertTrue(
            $form->isValidResource(),
            'ResourceForm claims that a valid statusdat resource is not valid'
        );
    }

    public function testInvalidStatusdatResourceIsNotValid()
    {
        $form = $this->buildResourceForm(
            new Zend_Config(
                array(
                    'type'          => 'statusdat',
                    'status_file'   => 'not_existing',
                    'object_file'   => 'not_existing'
                )
            )
        );

        $this->assertFalse(
            $form->isValidResource(),
            'ResourceForm claims that an invalid statusdat resource is valid'
        );
    }

    protected function buildResourceForm($resourceConfig)
    {
        $form = new ResourceForm();
        $form->setRequest($this->getRequestMock());
        $form->setResource($resourceConfig);
        $form->create();

        return $form;
    }

    protected function getRequestMock()
    {
        return Mockery::mock('\Zend_Controller_Request_Abstract')
            ->shouldReceive('getParam')
            ->with(Mockery::type('string'), Mockery::type('string'))
            ->andReturnUsing(function ($name, $default) { return $default; })
            ->getMock();
    }

    protected function setUpResourceFactoryMock($resourceMock)
    {
        Mockery::mock('alias:Icinga\Data\ResourceFactory')
            ->shouldReceive('createResource')
            ->with(Mockery::type('\Zend_Config'))
            ->andReturn($resourceMock);
    }
}
