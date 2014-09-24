<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config\Resource;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\Form\Config\Resource\LivestatusResourceForm;

class LivestatusResourceFormTest extends BaseTestCase
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
    public function testValidLivestatusResourceIsValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('connect')->andReturn(Mockery::self())
                ->shouldReceive('disconnect')->getMock()
        );

        $form = new LivestatusResourceForm();

        $this->assertTrue(
            $form->isValidResource($form),
            'ResourceForm claims that a valid livestatus resource is not valid'
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInvalidLivestatusResourceIsNotValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('connect')->once()->andThrow('\Exception')->getMock()
        );

        $form = new LivestatusResourceForm();

        $this->assertFalse(
            $form->isValidResource($form),
            'ResourceForm claims that an invalid livestatus resource is valid'
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
