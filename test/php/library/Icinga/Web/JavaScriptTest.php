<?php

namespace Tests\Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Test\BaseTestCase;
use Icinga\Web\JavaScript;
use SplFileObject;

class JavaScriptTest extends BaseTestCase
{
    protected $fileRoot;

    public function setUp(): void
    {
        parent::setUp();

        $this->fileRoot = Icinga::app()->getBaseDir('test/config/JavaScriptTest');
    }

    public function testLocalDefineOptimizations()
    {
        $expected = <<<'JS'
/* Relative path, No extension */
define("JavaScriptTest/someThing", ["JavaScriptTest/someThing/Else"], function (Else) {

});

JS;
        $someThing = $this->getFile('someThing.js');
        $this->assertSame($expected, $this->optimizeFile($someThing));

        $expected = <<<'JS'
/* Relative path outside the current directory, With extension */
define("JavaScriptTest/someThing/Else", ["JavaScriptTest/someOther"], function (someOther) {

});

JS;
        $someThingElse = $this->getFile('someThing/Else.js');
        $this->assertSame($expected, $this->optimizeFile($someThingElse));
    }

    public function testNoRequirementsOptimization()
    {
        $expected = <<<'JS'
define("JavaScriptTest/noRequirements", [], function () {

});

JS;
        $source = <<<'JS'
define(function () {

});

JS;
        $this->assertSame($expected, JavaScript::optimizeDefine(
            $source,
            'JavaScriptTest/noRequirements',
            'JavaScriptTest',
            'JavaScriptTest'
        ));
    }

    public function testGlobalRequirementsOptimization()
    {
        $expected = <<<'JS'
define("JavaScriptTest/globalRequirements", ["SomeOtherTest/Anything"], function (Anything) {

});

JS;
        $source = <<<'JS'
define(["SomeOtherTest/Anything"], function (Anything) {

});

JS;
        $this->assertSame($expected, JavaScript::optimizeDefine(
            $source,
            'JavaScriptTest/globalRequirements',
            'JavaScriptTest',
            'JavaScriptTest'
        ));
    }

    protected function optimizeFile(SplFileObject $file): string
    {
        return JavaScript::optimizeDefine(
            $file->fread($file->getSize()),
            $file->getRealPath(),
            $this->fileRoot,
            'JavaScriptTest'
        );
    }

    protected function getFile(string $file): SplFileObject
    {
        return new SplFileObject(join(DIRECTORY_SEPARATOR, [$this->fileRoot, $file]));
    }
}
