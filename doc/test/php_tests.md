# Writing PHPUnit tests

## Test path and filename

The path where you should put your PHPUnit tests should reflect the path in the sourcetree, with test/php/ prepended. So
if you're testing a file library/Icinga/My/File.php the test file should be at test/php/library/Icinga/My/File.php. This
also applies for modules, where the test folder is underneath modules/myModule/test/php

## Example test skeleton

Let's assume you're testing a class MyClass underneath the MyModule module and the file can be found at
modules/mymodule/library/MyModule/Helper/MyClass.php.

    <?php
    // The namespace is the same namespace as the file to test has, but with 'Test' prepended
    namespace Test\Modules\MyModule\Helper;

    // Require the file and maybe others. The start point is always the applications
    // testss/php/ folder where the runtest executable can be found

    require_once '../../mymodule/library/MyModule/Helper/MyClass.php';

    class MyClassTest extends \PHPUnit_Framework_TestCase
    {
        public function testSomething()
        {
            $this->assertTrue(true, "Asserting that the world didn't end yet");
        }
    }

## Testing Singletons

When test methods **modify static** class properties (which is the case when using singletons), add the PHPUnit
[`@backupStaticAttributes enabled`](http://phpunit.de/manual/3.7/en/appendixes.annotations.html#appendixes.annotations.backupStaticAttributes)
annotation to their [DockBlock](http://www.phpdoc.org/docs/latest/for-users/phpdoc/basic-syntax.html#what-is-a-docblock)
in order to backup and restore static attributes before and after the method execution respectively. For reference you
should **document** that the test interacts with static attributes:

    <?php

    namespace My\Test;

    use \PHPUnit_Framework_TestCase;
    use My\CheesecakeFactory;

    class SingletonTest extends PHPUnit_Framework_TestCase
    {
        /**
         * Interact with static attributes
         *
         * Utilizes singleton CheesecakeFactory
         *
         * @backupStaticAttributes enabled
         */
        public function testThatInteractsWithStaticAttributes()
        {
            CheesecakeFactory::setOpeningHours(24);
            // ...
        }
    }

## Requirements and the dependency mess

### spl_autoload_register vs. require

When looking at our test classes, you'll notice that we don't use PHPs autoloader to automatically load dependency, but
write 'require_once' by ourselfs. This has the following reasons:

-   When writing tests, you to be aware of every dependency your testclass includes. With autoloading, it's not directly
    obvious which classes are included during runtime.
-   When mocking classes, you don't need to tell your autoloader to use this class instead of the one used in production
-   Tests can't be run isolated without an boostrap class initializing the autoloader

### How to avoid require_once massacres: LibraryLoader

The downside of this approach is obvious: Especially when writing compoment tests you end up writing a lot of 'require'
classes. In the worst case, the PHP require_once method doesn't recognize a path to be already included and ends up
with an 'Cannot redeclare class XY' issue.

To avoid this, you should implement a LibraryLoader class for your component that handles the require_once calls.
For example, the status.dat component tests has a TestLoader class that includes all dependencies of the component:


    namespace Tests\Icinga\Protocol\Statusdat;
    use Test\Icinga\LibraryLoader;

    require_once('library/Icinga/LibraryLoader.php');

    /**
    *   Load all required libraries to use the statusdat
    *   component in integration tests
    *
    **/
    class StatusdatTestLoader extends LibraryLoader
    {
        /**
        *   @see LibraryLoader::requireLibrary
        *
        **/
        public static function requireLibrary()
        {
            // include Zend requirements
            require_once 'Zend/Config.php';
            require_once 'Zend/Cache.php';
            require_once 'Zend/Log.php';

            // retrieve the path to the icinga library
            $libPath = self::getLibraryPath();

            // require library dependencies
            require_once($libPath."/Data/AbstractQuery.php");
            require_once($libPath."/Application/Logger.php");
            require_once($libPath."/Data/DatasourceInterface.php");

            // shorthand for the folder where the statusdat component can be found
            $statusdat = realpath($libPath."/Protocol/Statusdat/");
            require_once($statusdat."/View/AccessorStrategy.php");
            // ... a few more requires ...
            require_once($statusdat."/Query/Group.php");
        }
    }

Now an component test (like tests/php/library/Icinga/Protocol/Statusdat/ReaderTest.php) can avoid the require calls and
just use the requireLibrary method:

    use Icinga\Protocol\Statusdat\Reader as Reader;

    // Load library at once
    require_once("StatusdatTestLoader.php");
    StatusdatTestLoader::requireLibrary();

**Note**: This should be used for component tests, where you want to test the combination of your classes. When testing
  a single execution unit, like a method, it's often better to explicitly write your dependencies.

If you compare the first approach with the last one you will notice that, even if we produced more code in the end, our
test is more verbose in what it is doing. When someone is updating your test, he should easily see what tests are existing
and what scenarios are missing.
