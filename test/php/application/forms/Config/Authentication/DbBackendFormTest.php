<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config\Authentication;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\Form\Config\Authentication\DbBackendForm;

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
        Mockery::mock('alias:Icinga\Authentication\UserBackend')
            ->shouldReceive('create')->with('test', Mockery::type('\Zend_Config'))->andReturnUsing(
                function () { return Mockery::mock(array('count' => 1)); }
        );

        $form = new DbBackendForm();
        $form->setBackendName('test');
        $form->setResources(array('test_db_backend' => null));
        $form->create();
        $form->populate(array('backend_test_resource' => 'test_db_backend'));

        $this->assertTrue(
            $form->isValidAuthenticationBackend(),
            'DbBackendForm claims that a valid authentication backend with users is not valid'
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInvalidBackendIsNotValid()
    {
        Mockery::mock('alias:Icinga\Authentication\UserBackend')
            ->shouldReceive('create')->with('test', Mockery::type('\Zend_Config'))->andReturnUsing(
                function () { return Mockery::mock(array('count' => 0)); }
        );

        $form = new DbBackendForm();
        $form->setBackendName('test');
        $form->setResources(array('test_db_backend' => null));
        $form->create();
        $form->populate(array('backend_test_resource' => 'test_db_backend'));

        $this->assertFalse(
            $form->isValidAuthenticationBackend(),
            'DbBackendForm claims that an invalid authentication backend without users is valid'
        );
    }
}
