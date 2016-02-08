<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Application;

use Icinga\Test\BaseTestCase;
use Icinga\Application\ClassLoader;

class ClassLoaderTest extends BaseTestCase
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

    public function setUp()
    {
        parent::setUp();
        $tempDir = sys_get_temp_dir();
        $this->baseDir = tempnam($tempDir, 'icinga2-web');
        system('mkdir -p '. $this->baseDir. dirname(self::$classFile));
        file_put_contents($this->baseDir. self::$classFile, self::$classContent);
    }

    public function tearDown()
    {
        parent::tearDown();
        system('rm -rf '. $this->baseDir);
    }

    public function testObjectCreation1()
    {
        $loader = new ClassLoader();
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

        $loader->unregister();

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
        $loader = new ClassLoader();
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

        $loader = new ClassLoader();
        $loader->registerNamespace('My\\Library', dirname($classFile));
        $this->assertFalse($loader->loadClass('DOES\\NOT\\EXISTS'));
        $this->assertTrue($loader->loadClass('My\\Library\\TestStruct'));
    }

    public function testClassLoad()
    {
        $classFile = $this->baseDir. self::$classFile;
        $this->assertFileExists($classFile);

        $loader = new ClassLoader();
        $loader->registerNamespace('My\\Library', dirname($classFile));
        $loader->register();

        $o = new \My\Library\TestStruct();
        $this->assertTrue($o->testFlag());
    }
}
