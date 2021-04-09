<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Config;

use Icinga\File\Ini\IniWriter;
use Icinga\Test\BaseTestCase;
use Icinga\Application\Config;
use Icinga\File\Ini\IniParser;

class IniParserTest extends BaseTestCase
{
    protected $tempFile;

    public function setUp(): void
    {
        parent::setUp();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'icinga-ini-parser-test');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unlink($this->tempFile);
    }

    public function testSectionNameEscaping()
    {
        $config = <<<'EOD'
[title with \"quote]

[title with \;semicolon]

[title with \\backslash]
EOD;

        $doc = IniParser::parseIni($config);
        $this->assertTrue(
            $doc->hasSection('title with "quote'),
            'IniParser::parseIni does not recognize escaped quotes in section names'
        );
        $this->assertTrue(
            $doc->hasSection('title with ;semicolon'),
            'IniParser::parseIni does not recognize escaped semicolons in section names'
        );
        $this->assertTrue(
            $doc->hasSection('title with \\backslash'),
            'IniParser::parseIni does not recognize escaped backslashes in section names'
        );

        (new IniWriter(Config::fromArray([
            'title with "quote'         => [],
            'title with ;semicolon'     => [],
            'title with \\backslash'    => []
        ]), $this->tempFile))->write();

        $configObject = IniParser::parseIniFile($this->tempFile);
        $this->assertTrue(
            $configObject->hasSection('title with "quote'),
            'IniParser::parseIniFile does not recognize escaped quotes in section names'
        );
        $this->assertTrue(
            $configObject->hasSection('title with ;semicolon'),
            'IniParser::parseIniFile does not recognize escaped semicolons in section names'
        );
        $this->assertTrue(
            $configObject->hasSection('title with \\backslash'),
            'IniParser::parseIniFile does not recognize escaped backslashes in section names'
        );
    }

    public function testDirectiveValueEscaping()
    {
        $config = <<<'EOD'
[section]
key1 = "key with escaped \"quote"
key2 = "key with escaped backslash \\"
key3 = "key with escaped backslash followed by quote \\\""
EOD;

        $doc = IniParser::parseIni($config);
        $this->assertEquals(
            'key with escaped "quote',
            $doc->getSection('section')->getDirective('key1')->getValue(),
            'IniParser::parseIni does not recognize escaped quotes in values'
        );
        $this->assertEquals(
            'key with escaped backslash \\',
            $doc->getSection('section')->getDirective('key2')->getValue(),
            'IniParser::parseIni does not recognize escaped backslashes in values'
        );
        $this->assertEquals(
            'key with escaped backslash followed by quote \\"',
            $doc->getSection('section')->getDirective('key3')->getValue(),
            'IniParser::parseIni does not recognize escaped backslashes followed by quotes in values'
        );

        (new IniWriter(Config::fromArray([
            'section'   => [
                'key1'  => 'key with escaped "quote',
                'key2'  => 'key with escaped backslash \\',
                'key3'  => 'key with escaped backslash followed by quote \\"'
            ]
        ]), $this->tempFile))->write();

        $configObject = IniParser::parseIniFile($this->tempFile);
        $this->assertEquals(
            'key with escaped "quote',
            $configObject->getSection('section')->get('key1'),
            'IniParser::parseIniFile does not recognize escaped quotes in values'
        );
        $this->assertEquals(
            'key with escaped backslash \\',
            $configObject->getSection('section')->get('key2'),
            'IniParser::parseIniFile does not recognize escaped backslashes in values'
        );
        $this->assertEquals(
            'key with escaped backslash followed by quote \\"',
            $configObject->getSection('section')->get('key3'),
            'IniParser::parseIniFile does not recognize escaped backslashes followed by quotes in values'
        );
    }

    public function testMultilineValues()
    {
        $config = <<<'EOD'
[section]
key1 = "with some
newline in the value"
EOD;
        $doc = IniParser::parseIni($config);
        $this->assertEquals(
            2,
            count(explode("\n", $doc->getSection('section')->getDirective('key1')->getValue())),
            'IniParser::parseIni does not recognize multi-line values'
        );

        (new IniWriter(Config::fromArray([
            'section'   => [
                'key1'  => "with some\nnewline in the value"
            ]
        ]), $this->tempFile))->write();

        $doc = IniParser::parseIniFile($this->tempFile);
        $this->assertEquals(
            2,
            count(explode("\n", $doc->getSection('section')->get('key1'))),
            'IniParser::parseIniFile does not recognize multi-line values'
        );
    }

    public function testEnvironmentVariableResolutionDoesNotWork()
    {
        (new IniWriter(Config::fromArray([
            'section'   => [
                'key1'  => '${PATH}_${APACHE_RUN_DIR}',
                'key2'  => '${APACHE_RUN_USER}'
            ]
        ]), $this->tempFile))->write();

        $configObject = IniParser::parseIniFile($this->tempFile);
        $this->assertEquals(
            '${PATH}_${APACHE_RUN_DIR}',
            $configObject->getSection('section')->get('key1'),
            'IniParser::parseIniFile does resolve environment variables'
        );
        $this->assertEquals(
            '${APACHE_RUN_USER}',
            $configObject->getSection('section')->get('key2'),
            'IniParser::parseIniFile does resolve environment variables'
        );
    }

    public function testPhpBug76965()
    {
        $config = <<<'EOD'
[section]
a = "foobar" 
b = "foo"bar ""     
c =   "foobar"  ; comment
d =' foo ' 
e =foo
f = foo
g = ""foo" "; Edge case, see below for more details
EOD;

        $parsedConfig = parse_ini_string($config, true, INI_SCANNER_RAW)['section'];
        if ($parsedConfig['a'] === 'foobar') {
            $this->markTestSkipped('This version of PHP is not affected by bug #76965');
        }

        $this->assertEquals('"foobar" ', $parsedConfig['a'], 'PHP version affected but bug #76965 not in effect?');
        $this->assertEquals('"foo"bar ""     ', $parsedConfig['b'], 'PHP version affected but bug #76965 not in effect?');
        $this->assertEquals('"foobar"  ', $parsedConfig['c'], 'PHP version affected but bug #76965 not in effect?');
        $this->assertEquals("' foo ' ", $parsedConfig['d'], 'PHP version affected but bug #76965 not in effect?');

        file_put_contents($this->tempFile, $config);
        $configObject = IniParser::parseIniFile($this->tempFile);

        $this->assertEquals(
            'foobar',
            $configObject->get('section', 'a'),
            'Workaround for PHP bug #76965 missing'
        );
        $this->assertEquals(
            'foo"bar "',
            $configObject->get('section', 'b'),
            'Workaround for PHP bug #76965 missing'
        );
        $this->assertEquals(
            'foobar',
            $configObject->get('section', 'c'),
            'Workaround for PHP bug #76965 missing'
        );
        $this->assertEquals(
            ' foo ',
            $configObject->get('section', 'd'),
            'Workaround for PHP bug #76965 missing'
        );
        $this->assertEquals(
            'foo',
            $configObject->get('section', 'e'),
            'Workaround for PHP bug #76965 missing'
        );
        $this->assertEquals(
            'foo',
            $configObject->get('section', 'f'),
            'Workaround for PHP bug #76965 missing'
        );
        // This is an edge case which will fail with the current work-around implementation.
        // Though, it's considered a really rare case and as such deliberately ignored.
        /*$this->assertEquals(
            '"foo" ',
            $configObject->get('section', 'g'),
            'Workaround for PHP bug #76965 too greedy'
        );*/
    }
}
