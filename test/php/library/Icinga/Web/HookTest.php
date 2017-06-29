<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Hook;

use Icinga\Web\Hook;

class TestHook extends Hook
{
}

namespace Tests\Icinga\Web;

use Icinga\Test\BaseTestCase;
use Icinga\Web\Hook;
use Icinga\Web\Hook\TestHook;
use Exception;

class NoHook
{
}
class MyHook extends TestHook
{
}
class AnotherHook extends TestHook
{
}
class FailingHook extends TestHook
{
    public function __construct()
    {
        throw new Exception("I'm failing");
    }
}

class HookTest extends BaseTestCase
{
    protected $invalidHook   = '\\Tests\\Icinga\\Web\\NoHook';
    protected $validHook     = '\\Tests\\Icinga\\Web\\MyHook';
    protected $anotherHook   = '\\Tests\\Icinga\\Web\\AnotherHook';
    protected $failingHook   = '\\Tests\\Icinga\\Web\\FailingHook';
    protected $testBaseClass = '\\Icinga\\Web\\Hook\\TestHook';

    public function setUp()
    {
        $this->markTestSkipped();
        parent::setUp();
        Hook::clean();
    }

    public function tearDown()
    {
        parent::tearDown();
        Hook::clean();
    }

    public function testKnowsWhichHooksAreRegistered()
    {
        Hook::register('test', __FUNCTION__, $this->validHook);
        $this->assertTrue(Hook::has('test'));
        $this->assertFalse(Hook::has('no_such_hook'));
    }

    public function testCorrectlyHandlesMultipleInstances()
    {
        Hook::register('test', 'one', $this->validHook);
        Hook::register('test', 'two', $this->anotherHook);
        $this->assertInstanceOf(
            $this->anotherHook,
            Hook::createInstance('test', 'two')
        );
        $this->assertInstanceOf(
            $this->validHook,
            Hook::createInstance('test', 'one')
        );
    }

    public function testReturnsNullForInvalidHooks()
    {
        $this->assertNull(
            Hook::createInstance('not_existing', __FUNCTION__),
            'Hook::createInstance does not return null if given an unknown hook'
        );
    }

    public function testReturnsNullForFailingHook()
    {
        Hook::register('test', __FUNCTION__, $this->failingHook);
        $this->assertNull(Hook::createInstance('test', __FUNCTION__));
    }

    public function testChecksWhetherCreatedInstancesInheritBaseClasses()
    {
        Hook::register('test', __FUNCTION__, $this->validHook);
        $this->assertInstanceOf(
            $this->testBaseClass,
            Hook::createInstance('test', __FUNCTION__)
        );
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     */
    public function testThrowsErrorsForInstancesNotInheritingBaseClasses()
    {
        Hook::register('test', __FUNCTION__, $this->invalidHook);
        Hook::createInstance('test', __FUNCTION__);
    }

    public function testCreatesIdenticalInstancesOnlyOnce()
    {
        Hook::register('test', __FUNCTION__, $this->validHook);
        $first  = Hook::createInstance('test', __FUNCTION__);
        $second = Hook::createInstance('test', __FUNCTION__);

        $this->assertSame($first, $second);
    }

    public function testReturnsAnEmptyArrayWithNoRegisteredHook()
    {
        $this->assertEquals(array(), Hook::all('not_existing'));
    }
}
