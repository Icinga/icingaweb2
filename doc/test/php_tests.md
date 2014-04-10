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
