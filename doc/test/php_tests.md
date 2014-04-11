# Writing PHPUnit tests

## Test path and filename

The path where you should put your PHPUnit tests should reflect the path in the sourcetree, with test/php/ prepended. So
if you're testing a file library/Icinga/My/File.php the test file should be at test/php/library/Icinga/My/File.php. This
also applies for modules, where the tests are underneath modules/myModule/test/php

## Example test skeleton

Let's assume you're testing a class MyClass underneath the MyModule module and the file can be found at
modules/mymodule/library/MyModule/Helper/MyClass.php.

    <?php
    // The namespace is the same namespace as the file to test has, but with 'Tests' prepended
    namespace Tests\Module\MyModule\Helper;

    class MyClassTest extends \PHPUnit_Framework_TestCase
    {
        public function testSomething()
        {
            $this->assertTrue(true, "Asserting that the world didn't end yet");
        }
    }

## Testing Singletons

When test methods **modify static** class properties (which is the case when using singletons), do not add the PHPUnit
[`@backupStaticAttributes enabled`](http://phpunit.de/manual/3.7/en/appendixes.annotations.html#appendixes.annotations.backupStaticAttributes)
annotation to their [DocBlock](http://www.phpdoc.org/docs/latest/for-users/phpdoc/basic-syntax.html#what-is-a-docblock)
in order to backup and restore static attributes before and after the test execution respectively. Use the setUp()
and tearDown() routines instead to accomplish this task.

    <?php

    namespace My\Test;

    use Icinga\Test\BaseTestCase;
    use My\CheesecakeFactory;

    class SingletonTest extends BaseTestCase
    {
        protected function setUp()
        {
            parent::setUp();
            $this->openingHours = CheesecakeFactory::getOpeningHours();
        }

        protected function tearDown()
        {
            parent::tearDown();
            CheesecakeFactory::setOpeningHours($this->openingHours);
        }

        public function testThatInteractsWithStaticAttributes()
        {
            CheesecakeFactory::setOpeningHours(24);
            // ...
        }
    }

The reason to avoid using @backupStaticAttributes is the fact that if it is necessary to utilize a
singleton in your *unit* tests you probably want to rethink what you are going to test and because
some tests are using the mock framework [`Mockery`](https://github.com/padraic/mockery) which is
using static class properties to implement its caching mechanics.
