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

    public function setUp()
    {
        parent::setUp();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'icinga-ini-parser-test');
    }

    public function tearDown()
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
            'IniParser does not recognize multi-line values'
        );
    }
}
