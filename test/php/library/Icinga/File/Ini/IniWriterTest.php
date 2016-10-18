<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Config;

use Icinga\File\Ini\IniWriter;
use Icinga\Test\BaseTestCase;
use Icinga\Application\Config;

class IniWriterTest extends BaseTestCase
{
    protected $tempFile;
    protected $tempFile2;

    public function setUp()
    {
        parent::setUp();

        $this->tempFile = tempnam(sys_get_temp_dir(), 'icinga-ini-writer-test');
        $this->tempFile2 = tempnam(sys_get_temp_dir(), 'icinga-ini-writer-test-2');
    }

    public function tearDown()
    {
        parent::tearDown();

        unlink($this->tempFile);
        unlink($this->tempFile2);
    }

    public function testWhetherPointInSectionIsNotNormalized()
    {
        $writer = new IniWriter(
            Config::fromArray(
                array(
                    'section' => array(
                        'foo.bar' => 1337
                    ),
                    'section.with.multiple.dots' => array(
                        'some more.nested stuff' => 'With more values'
                    )
                )
            ),
            $this->tempFile
        );
        $writer->write();
        $config = Config::fromIni($this->tempFile)->toArray();
        $this->assertTrue(array_key_exists('section.with.multiple.dots', $config), 'Section names not normalized');
    }

    public function testWhetherNestedPropertiesAreInserted()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $config = Config::fromArray(array('a' => array('b' => 'c')));
        $writer = new IniWriter($config, $target);
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertInstanceOf(
            'Icinga\Data\ConfigObject',
            $newConfig->getSection('a'),
            'IniWriter does not insert nested properties'
        );
        $this->assertEquals(
            'c',
            $newConfig->getSection('a')->get('b'),
            'IniWriter does not insert nested properties'
        );
    }

    public function testWhetherSectionOrderIsUpdated()
    {
        $config = <<<'EOD'
[one]
key1 = "1"
key2 = "2"

[two]
a.b = "c"
d.e = "f"

[three]
key = "value"
foo.bar = "raboof"
EOD;

        $reverted = <<<'EOD'
[three]
key = "value"
foo.bar = "raboof"

[two]
a.b = "c"
d.e = "f"

[one]
key1 = "1"
key2 = "2"
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            Config::fromArray(
                array(
                    'three' => array(
                        'foo.bar' => 'raboof',
                        'key' => 'value'
                    ),
                    'two' => array(
                        'd.e' => 'f',
                        'a.b' => 'c'
                    ),
                    'one' => array(
                        'key2' => '2',
                        'key1' => '1'
                    )
                )
            ),
            $target
        );

        $this->assertEquals(
            trim($reverted),
            trim($writer->render()),
            'IniWriter does not preserve section and/or property order'
        );
    }

    public function testWhetherCommentOrderIsUpdated()
    {
        $config = <<<'EOD'
; comment 1
[one]

; comment 2
[two]
EOD;

        $reverted = <<<'EOD'
; comment 2
[two]

; comment 1
[one]
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            Config::fromArray(
                array(
                    'two' => array(),
                    'one' => array()
                )
            ),
            $target
        );

        $this->assertEquals(
            trim($reverted),
            trim($writer->render()),
            'IniWriter does not preserve section and/or property order'
        );
    }


    public function testWhetherCommentsOnEmptyLinesArePreserved()
    {
        $config = <<<'EOD'
; some interesting comment
[blarg]
key = "value"

; some dangling comment
; boring comment
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(Config::fromArray(array('blarg' => array('key' => 'value'))), $target);

        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not preserve comments on empty lines'
        );
    }

    public function testWhetherCommentsOnPropertyLinesArePreserved()
    {
        $config = <<<'EOD'
[blarg]
foo = "1337" ; I know what a " and a ' is
bar = "7331" ; I; tend; to; overact; !1!1!!11!111!                                      ;
key = "value" ; some comment for a small sized property
xxl = "very loooooooooooooooooooooong" ; my value is very lo...
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            Config::fromArray(
                array('blarg' => array(
                    'foo' => 1337,
                    'bar' => 7331,
                    'key' => 'value',
                    'xxl' => 'very loooooooooooooooooooooong'
                ))
            ),
            $target
        );
        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not preserve comments on property lines'
        );
    }

    public function testWhetherCommentsOnEmptySectionLinesArePreserved()
    {
        $config = <<<'EOD'
[section]
; some interesting comment, in a section
key = "value"
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(Config::fromArray(array('section' => array('key' => 'value'))), $target);

        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not preserve comments on empty section lines'
        );
    }

    public function testWhetherCommentsOnSectionPropertyLinesArePreserved()
    {
        $config = <<<'EOD'
[section]
foo = "1337" ; I know what a " and a ' is
bar = "7331" ; I; tend; to; overact; !1!1!!11!111!                                      ;
key = "value" ; some comment for a small sized property
xxl = "very loooooooooooooooooooooong" ; my value is very lo...
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            Config::fromArray(
                array(
                    'section' => array(
                        'foo' => 1337,
                        'bar' => 7331,
                        'key' => 'value',
                        'xxl' => 'very loooooooooooooooooooooong'
                    )
                )
            ),
            $target
        );

        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not preserve comments on property lines'
        );
    }

    public function testWhetherLinebreaksAreProcessed()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $writer = new IniWriter(
            Config::fromArray(
                array(
                    'section' => array(
                        'foo' => 'linebreak
in line',
                        'linebreak
inkey' => 'blarg'
                    )
                )
            ),
            $target
        );

        $rendered = $writer->render();
        $this->assertEquals(
            count(explode("\n", $rendered)),
            5,
            'generated config should not contain more than three line breaks'
        );
    }

    public function testSectionNameEscaping()
    {
        $config = <<<'EOD'
[section [brackets\]]
foo = "bar"

[section \;comment]
foo = "bar"

[section \"quotes\"]
foo = "bar"

[section with \\]
foo = "bar"

[section with newline]
foo = "bar"
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            Config::fromArray(
                array(
                    'section [brackets]' => array('foo' => 'bar'),
                    'section ;comment' => array('foo' => 'bar'),
                    'section "quotes"' => array('foo' => 'bar'),
                    'section with \\' => array('foo' => 'bar'),
                    'section with' . PHP_EOL . 'newline' => array('foo' => 'bar')
                )
            ),
            $target
        );

        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not handle special chars in section names properly.'
        );
    }

    public function testDirectiveValueEscaping()
    {
        $config = <<<'EOD'
[section]
key1 = "value with \"quotes\""
key2 = "value with \\"

EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            Config::fromArray(
                array(
                    'section' => array(
                        'key1' => 'value with "quotes"',
                        'key2' => 'value with \\'
                    )
                )
            ),
            $target
        );

        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not handle special chars in directives properly.'
        );
    }

    public function testSectionDeleted()
    {
        $config = <<<'EOD'
[section 1]
guarg = "1"

[section 2]
foo = "1337"
foo2 = "baz"
foo3 = "nope"
foo4 = "bar"

[section 3]
guard = "2"
EOD;
        $deleted = <<<'EOD'
[section 1]
guarg = "1"

[section 3]
guard = "2"
EOD;

        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            Config::fromArray(array(
                'section 1' => array('guarg' => 1),
                'section 3' => array('guard' => 2)
            )),
            $target
        );

        $this->assertEquals(
            trim($deleted),
            trim($writer->render()),
            'IniWriter does not delete sections properly'
        );
    }

    /**
     * Write a INI-configuration string to a temporary file and return its path
     *
     * @param   string      $config     The config string to write
     *
     * @return  string                  The path to the temporary file
     */
    protected function writeConfigToTemporaryFile($config)
    {
        file_put_contents($this->tempFile, $config);
        return $this->tempFile;
    }

    public function testWhetherNullValuesGetPersisted()
    {
        $config = Config::fromArray(array());
        $section = $config->getSection('garbage');
        $section->foobar = null;
        $config->setSection('garbage', $section);

        $iniWriter = new IniWriter($config, '/dev/null');
        $this->assertNotContains(
            'foobar',
            $iniWriter->render(),
            'IniWriter persists section keys with null values'
        );
    }

    public function testWhetherEmptyValuesGetPersisted()
    {
        $config = Config::fromArray(array());
        $section = $config->getSection('garbage');
        $section->foobar = '';
        $config->setSection('garbage', $section);

        $iniWriter = new IniWriter($config, '/dev/null');
        $this->assertContains(
            'foobar',
            $iniWriter->render(),
            'IniWriter doesn\'t persist section keys with empty values'
        );
    }

    public function testExplicitRemove()
    {
        $filename = tempnam(sys_get_temp_dir(), 'iw2');
        $config = Config::fromArray(array('garbage' => array('foobar' => 'lolcat')));
        $iniWriter = new IniWriter($config, $filename);
        $iniWriter->write();

        $section = $config->getSection('garbage');
        $section->foobar = null;
        $iniWriter = new IniWriter($config, $filename);
        $this->assertNotContains(
            'foobar',
            $iniWriter->render(),
            'IniWriter doesn\'t remove section keys with null values'
        );

        unlink($filename);
    }
}
