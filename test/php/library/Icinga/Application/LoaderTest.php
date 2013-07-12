<?php

namespace Tests\Icinga\Application;

require_once __DIR__. '/../../../../../library/Icinga/Exception/ProgrammingError.php';
require_once __DIR__. '/../../../../../library/Icinga/Application/Loader.php';

use Icinga\Application\Loader;

/**
*
* Test class for Loader 
* Created Thu, 07 Feb 2013 10:07:13 +0000 
*
**/
class LoaderTest extends \PHPUnit_Framework_TestCase
{
    private static $classFile = 'test/My/Library/TestStruct.php';

    private $baseDir;

    private static $classContent =  <<<'EOD'
<?php
namespace My\Library;

class TestStruct
{
    public function testFlag()
    {
        return true;
    }
}

EOD;

    protected function setUp()
    {
        $tempDir = sys_get_temp_dir();
        $this->baseDir = tempnam($tempDir, 'icinga2-web');
        system('mkdir -p '. $this->baseDir. dirname(self::$classFile));
        file_put_contents($this->baseDir. self::$classFile, self::$classContent);
    }

    protected function tearDown()
    {
        system('rm -rf '. $this->baseDir);
    }


    public function testObjectCreation1()
    {
        $loader = new Loader();
        $loader->register();

        $check = false;
        foreach (spl_autoload_functions() as $functions) {
            if (is_array($functions) && $functions[0] === $loader) {
                if ($functions[1] === 'loadClass') {
                    $check = true;
                }
            }
        }
        $this->assertTrue($check);

        $loader->unRegister();

        $check = true;
        foreach (spl_autoload_functions() as $functions) {
            if (is_array($functions) && $functions[0] === $loader) {
                if ($functions[1] === 'loadClass') {
                    $check = false;
                }
            }
        }
        $this->assertTrue($check);
    }

    public function testNamespaces()
    {
        $loader = new Loader();
        $loader->registerNamespace('Test\\Laola', '/tmp');
        $loader->registerNamespace('Dings\\Var', '/var/tmp');

        $this->assertTrue($loader->hasNamespace('Dings\\Var'));
        $this->assertTrue($loader->hasNamespace('Test\\Laola'));
    }

    /**
     * Important: Test must run before testClassLoad
     *
     * Because testClassLoads loads the real code into stack
     */
    public function testLoadInterface()
    {
        $classFile = $this->baseDir. self::$classFile;
        $this->assertFileExists($classFile);

        $loader = new Loader();
        $loader->registerNamespace('My\\Library', dirname($classFile));
        $this->assertFalse($loader->loadClass('DOES\\NOT\\EXISTS'));
        $this->assertTrue($loader->loadClass('My\\Library\\TestStruct'));
    }

    public function testClassLoad()
    {
        $classFile = $this->baseDir. self::$classFile;
        $this->assertFileExists($classFile);

        $loader = new Loader();
        $loader->registerNamespace('My\\Library', dirname($classFile));
        $loader->register();

        $o = new \My\Library\TestStruct();
        $this->assertTrue($o->testFlag());
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage Directory does not exist: /trullalla/123
     */
    public function testNonexistingDirectory()
    {
        $loader = new Loader();
        $loader->registerNamespace('My\\Library', '/trullalla/123');
    }
}
