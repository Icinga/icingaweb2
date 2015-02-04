<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Forms\Config\Resource;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\Forms\Config\Resource\DbResourceForm;

class DbResourceFormTest extends BaseTestCase
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
    public function testValidDbResourceIsValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('getConnection')->atMost()->twice()->andReturn(Mockery::self())->getMock()
        );

        $this->assertTrue(
            DbResourceForm::isValidResource(new DbResourceForm()),
            'ResourceForm claims that a valid db resource is not valid'
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInvalidDbResourceIsNotValid()
    {
        $this->setUpResourceFactoryMock(
            Mockery::mock()->shouldReceive('getConnection')->once()->andThrow('\Exception')->getMock()
        );

        $this->assertFalse(
            DbResourceForm::isValidResource(new DbResourceForm()),
            'ResourceForm claims that an invalid db resource is valid'
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
