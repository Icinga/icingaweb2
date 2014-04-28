<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web;

use Mockery;
use Exception;
use Icinga\Web\Hook;
use Icinga\Test\BaseTestCase;

class ErrorProneHookImplementation
{
    public function __construct()
    {
        throw new Exception();
    }
}

class HookTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        Hook::clean();
    }

    public function tearDown()
    {
        parent::tearDown();
        Hook::clean();
    }

    public function testWhetherHasReturnsTrueIfGivenAKnownHook()
    {
        Hook::registerClass('TestHook', __FUNCTION__, get_class(Mockery::mock(Hook::$BASE_NS . 'TestHook')));

        $this->assertTrue(Hook::has('TestHook'), 'Hook::has does not return true if given a known hook');
    }

    public function testWhetherHasReturnsFalseIfGivenAnUnknownHook()
    {
        $this->assertFalse(Hook::has('not_existing'), 'Hook::has does not return false if given an unknown hook');
    }

    public function testWhetherHooksCanBeRegisteredWithRegisterClass()
    {
        Hook::registerClass('TestHook', __FUNCTION__, get_class(Mockery::mock(Hook::$BASE_NS . 'TestHook')));

        $this->assertTrue(Hook::has('TestHook'), 'Hook::registerClass does not properly register a given hook');
    }

    /**
     * @depends testWhetherHooksCanBeRegisteredWithRegisterClass
     */
    public function testWhetherMultipleHooksOfTheSameTypeCanBeRegisteredWithRegisterClass()
    {
        $firstHook = Mockery::mock(Hook::$BASE_NS . 'TestHook');
        $secondHook = Mockery::mock('overload:' . get_class($firstHook));

        Hook::registerClass('TestHook', 'one', get_class($firstHook));
        Hook::registerClass('TestHook', 'two', get_class($secondHook));
        $this->assertInstanceOf(
            get_class($secondHook),
            Hook::createInstance('TestHook', 'two'),
            'Hook::registerClass is not able to register different hooks of the same type'
        );
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     */
    public function testWhetherOnlyClassesCanBeRegisteredAsHookWithRegisterClass()
    {
        Hook::registerClass('TestHook', __FUNCTION__, 'nope');
    }

    public function testWhetherHooksCanBeRegisteredWithRegisterObject()
    {
        Hook::registerObject('TestHook', __FUNCTION__, Mockery::mock(Hook::$BASE_NS . 'TestHook'));

        $this->assertTrue(Hook::has('TestHook'), 'Hook::registerObject does not properly register a given hook');
    }

    /**
     * @depends testWhetherHooksCanBeRegisteredWithRegisterObject
     */
    public function testWhetherMultipleHooksOfTheSameTypeCanBeRegisteredWithRegisterObject()
    {
        $firstHook = Mockery::mock(Hook::$BASE_NS . 'TestHook');
        $secondHook = Mockery::mock('overload:' . get_class($firstHook));

        Hook::registerObject('TestHook', 'one', $firstHook);
        Hook::registerObject('TestHook', 'two', $secondHook);
        $this->assertInstanceOf(
            get_class($secondHook),
            Hook::createInstance('TestHook', 'two'),
            'Hook::registerObject is not able to register different hooks of the same type'
        );
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     */
    public function testWhetherOnlyObjectsCanBeRegisteredAsHookWithRegisterObject()
    {
        Hook::registerObject('TestHook', __FUNCTION__, 'nope');
    }

    public function testWhetherCreateInstanceReturnsNullIfGivenAnUnknownHookName()
    {
        $this->assertNull(
            Hook::createInstance('not_existing', __FUNCTION__),
            'Hook::createInstance does not return null if given an unknown hook'
        );
    }

    /**
     * @depends testWhetherHooksCanBeRegisteredWithRegisterClass
     */
    public function testWhetherCreateInstanceInitializesHooksInheritingFromAPredefinedAbstractHook()
    {
        $baseHook = Mockery::mock(Hook::$BASE_NS . 'TestHook');
        Hook::registerClass(
            'TestHook',
            __FUNCTION__,
            get_class(Mockery::mock('overload:' . get_class($baseHook)))
        );

        $this->assertInstanceOf(
            get_class($baseHook),
            Hook::createInstance('TestHook', __FUNCTION__),
            'Hook::createInstance does not initialize hooks inheriting from a predefined abstract hook'
        );
    }

    /**
     * @depends testWhetherHooksCanBeRegisteredWithRegisterClass
     */
    public function testWhetherCreateInstanceDoesNotInitializeMultipleHooksForASpecificIdentifier()
    {
        Hook::registerClass('TestHook', __FUNCTION__, get_class(Mockery::mock(Hook::$BASE_NS . 'TestHook')));
        $secondHook = Hook::createInstance('TestHook', __FUNCTION__);
        $thirdHook = Hook::createInstance('TestHook', __FUNCTION__);

        $this->assertSame(
            $secondHook,
            $thirdHook,
            'Hook::createInstance initializes multiple hooks for a specific identifier'
        );
    }

    /**
     * @depends testWhetherHooksCanBeRegisteredWithRegisterClass
     */
    public function testWhetherCreateInstanceReturnsNullIfHookCannotBeInitialized()
    {
        Hook::registerClass('TestHook', __FUNCTION__, 'Tests\Icinga\Web\ErrorProneHookImplementation');

        $this->assertNull(Hook::createInstance('TestHook', __FUNCTION__));
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @depends testWhetherHooksCanBeRegisteredWithRegisterClass
     */
    public function testWhetherCreateInstanceThrowsAnErrorIfGivenAHookNotInheritingFromAPredefinedAbstractHook()
    {
        Mockery::mock(Hook::$BASE_NS . 'TestHook');
        Hook::registerClass('TestHook', __FUNCTION__, get_class(Mockery::mock('TestHook')));

        Hook::createInstance('TestHook', __FUNCTION__);
    }

    /**
     * @depends testWhetherHooksCanBeRegisteredWithRegisterObject
     */
    public function testWhetherAllReturnsAllRegisteredHooks()
    {
        $hook = Mockery::mock(Hook::$BASE_NS . 'TestHook');
        Hook::registerObject('TestHook', 'one', $hook);
        Hook::registerObject('TestHook', 'two', $hook);
        Hook::registerObject('TestHook', 'three', $hook);

        $this->assertCount(3, Hook::all('TestHook'), 'Hook::all does not return all registered hooks');
    }

    public function testWhetherAllReturnsNothingIfGivenAnUnknownHookName()
    {
        $this->assertEmpty(
            Hook::all('not_existing'),
            'Hook::all does not return an empty array if given an unknown hook'
        );
    }

    /**
     * @depends testWhetherHooksCanBeRegisteredWithRegisterObject
     */
    public function testWhetherFirstReturnsTheFirstRegisteredHook()
    {
        $firstHook = Mockery::mock(Hook::$BASE_NS . 'TestHook');
        $secondHook = Mockery::mock(Hook::$BASE_NS . 'TestHook');
        Hook::registerObject('TestHook', 'first', $firstHook);
        Hook::registerObject('TestHook', 'second', $secondHook);

        $this->assertSame(
            $firstHook,
            Hook::first('TestHook'),
            'Hook::first does not return the first registered hook'
        );
    }
}
