<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Config;

use Icinga\File\Ini\Dom\Document;
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
[title with \]bracket]
key1 = "1"
key2 = "2"

[title with \"quote]
key1 = "1"
key2 = "2"
EOD;
        $doc = IniParser::parseIni($config);
        $this->assertTrue(
            $doc->hasSection('title with ]bracket'),
            'IniParser does not recognize escaped bracket in section'
        );
        $this->assertTrue(
            $doc->hasSection('title with "quote'),
            'IniParser does not recognize escaped quote in section'
        );
    }

    public function testDirectiveValueEscaping()
    {
        $config = <<<'EOD'
[section]
key1 = "key with escaped \"quote"
EOD;
        $doc = IniParser::parseIni($config);
        $this->assertEquals(
            'key with escaped "quote',
            $doc->getSection('section')->getDirective('key1')->getValue(),
            'IniParser does not recognize escaped bracket in section'
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
