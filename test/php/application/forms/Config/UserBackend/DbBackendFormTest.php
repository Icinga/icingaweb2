<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Forms\Config\UserBackend;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Data\ConfigObject;
use Icinga\Test\BaseTestCase;
use Icinga\Forms\Config\UserBackend\DbBackendForm;

class DbBackendFormTest extends BaseTestCase
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
        Mockery::mock('overload:Icinga\Authentication\User\DbUserBackend')
            ->shouldReceive('select->where->count')
            ->andReturn(2);

        // Passing array(null) is required to make Mockery call the constructor...
        $form = Mockery::mock('Icinga\Forms\Config\UserBackend\DbBackendForm[getView]', array(null));
        $form->shouldReceive('getView->escape')
            ->with(Mockery::type('string'))
            ->andReturnUsing(function ($s) { return $s; });
        $form->setTokenDisabled();
        $form->setResources(array('test_db_backend'));
        $form->populate(array('resource' => 'test_db_backend'));

        $this->assertTrue(
            DbBackendForm::isValidUserBackend($form),
            'DbBackendForm claims that a valid user backend with users is not valid'
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInvalidBackendIsNotValid()
    {
        $this->setUpResourceFactoryMock();
        Mockery::mock('overload:Icinga\Authentication\User\DbUserBackend')
            ->shouldReceive('count')
            ->andReturn(0);

        // Passing array(null) is required to make Mockery call the constructor...
        $form = Mockery::mock('Icinga\Forms\Config\UserBackend\DbBackendForm[getView]', array(null));
        $form->shouldReceive('getView->escape')
            ->with(Mockery::type('string'))
            ->andReturnUsing(function ($s) { return $s; });
        $form->setTokenDisabled();
        $form->setResources(array('test_db_backend'));
        $form->populate(array('resource' => 'test_db_backend'));

        $this->assertFalse(
            DbBackendForm::isValidUserBackend($form),
            'DbBackendForm claims that an invalid user backend without users is valid'
        );
    }

    protected function setUpResourceFactoryMock()
    {
        Mockery::mock('alias:Icinga\Data\ResourceFactory')
            ->shouldReceive('createResource')
            ->andReturn(Mockery::mock('Icinga\Data\Db\DbConnection'))
            ->shouldReceive('getResourceConfig')
            ->andReturn(new ConfigObject());
    }
}
