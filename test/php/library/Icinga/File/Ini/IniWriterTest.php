<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Config;

use Zend_Config;
use Zend_Config_Ini;
use Icinga\File\Ini\IniWriter;
use Icinga\Test\BaseTestCase;

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

    public function testWhetherSimplePropertiesAreInsertedInEmptyFiles()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $config = new Zend_Config(array('key' => 'value'));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertEquals('value', $newConfig->get('key'), 'IniWriter does not insert in empty files');
    }

    public function testWhetherSimplePropertiesAreInsertedInExistingFiles()
    {
        $target = $this->writeConfigToTemporaryFile('key1 = "1"');
        $config = new Zend_Config(array('key2' => '2'));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertEquals('2', $newConfig->get('key2'), 'IniWriter does not insert in existing files');
    }

    /**
     * @depends testWhetherSimplePropertiesAreInsertedInExistingFiles
     */
    public function testWhetherSimplePropertiesAreUpdated()
    {
        $target = $this->writeConfigToTemporaryFile('key = "value"');
        $config = new Zend_Config(array('key' => 'eulav'));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertEquals('eulav', $newConfig->get('key'), 'IniWriter does not update simple properties');
    }

    /**
     * @depends testWhetherSimplePropertiesAreInsertedInExistingFiles
     */
    public function testWhetherSimplePropertiesAreDeleted()
    {
        $target = $this->writeConfigToTemporaryFile('key = "value"');
        $config = new Zend_Config(array());
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertNull($newConfig->get('key'), 'IniWriter does not delete simple properties');
    }

    public function testWhetherNestedPropertiesAreInserted()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $config = new Zend_Config(array('a' => array('b' => 'c')));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertInstanceOf(
            '\Zend_Config',
            $newConfig->get('a'),
            'IniWriter does not insert nested properties'
        );
        $this->assertEquals(
            'c',
            $newConfig->get('a')->get('b'),
            'IniWriter does not insert nested properties'
        );
    }

    /**
     * @depends testWhetherNestedPropertiesAreInserted
     */
    public function testWhetherNestedPropertiesAreUpdated()
    {
        $target = $this->writeConfigToTemporaryFile('a.b = "c"');
        $config = new Zend_Config(array('a' => array('b' => 'cc')));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertInstanceOf(
            '\Zend_Config',
            $newConfig->get('a'),
            'IniWriter does not update nested properties'
        );
        $this->assertEquals(
            'cc',
            $newConfig->get('a')->get('b'),
            'IniWriter does not update nested properties'
        );
    }

    /**
     * @depends testWhetherNestedPropertiesAreInserted
     */
    public function testWhetherNestedPropertiesAreDeleted()
    {
        $target = $this->writeConfigToTemporaryFile('a.b = "c"');
        $config = new Zend_Config(array());
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertNull(
            $newConfig->get('a'),
            'IniWriter does not delete nested properties'
        );
    }

    public function testWhetherSimpleSectionPropertiesAreInserted()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $config = new Zend_Config(array('section' => array('key' => 'value')));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertInstanceOf(
            '\Zend_Config',
            $newConfig->get('section'),
            'IniWriter does not insert sections'
        );
        $this->assertEquals(
            'value',
            $newConfig->get('section')->get('key'),
            'IniWriter does not insert simple section properties'
        );
    }

    /**
     * @depends testWhetherSimpleSectionPropertiesAreInserted
     */
    public function testWhetherSimpleSectionPropertiesAreUpdated()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[section]
key = "value"
EOD
        );
        $config = new Zend_Config(array('section' => array('key' => 'eulav')));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertEquals(
            'eulav',
            $newConfig->get('section')->get('key'),
            'IniWriter does not update simple section properties'
        );
    }

    /**
     * @depends testWhetherSimpleSectionPropertiesAreInserted
     */
    public function testWhetherSimpleSectionPropertiesAreDeleted()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[section]
key = "value"
EOD
        );
        $config = new Zend_Config(array('section' => array()));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertNull(
            $newConfig->get('section')->get('key'),
            'IniWriter does not delete simple section properties'
        );
    }

    public function testWhetherNestedSectionPropertiesAreInserted()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $config = new Zend_Config(array('section' => array('a' => array('b' => 'c'))));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertInstanceOf(
            '\Zend_Config',
            $newConfig->get('section'),
            'IniWriter does not insert sections'
        );
        $this->assertInstanceOf(
            '\Zend_Config',
            $newConfig->get('section')->get('a'),
            'IniWriter does not insert nested section properties'
        );
        $this->assertEquals(
            'c',
            $newConfig->get('section')->get('a')->get('b'),
            'IniWriter does not insert nested section properties'
        );
    }

    /**
     * @depends testWhetherNestedSectionPropertiesAreInserted
     */
    public function testWhetherNestedSectionPropertiesAreUpdated()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[section]
a.b = "c"
EOD
        );
        $config = new Zend_Config(array('section' => array('a' => array('b' => 'cc'))));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertEquals(
            'cc',
            $newConfig->get('section')->get('a')->get('b'),
            'IniWriter does not update nested section properties'
        );
    }

    /**
     * @depends testWhetherNestedSectionPropertiesAreInserted
     */
    public function testWhetherNestedSectionPropertiesAreDeleted()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[section]
a.b = "c"
EOD
        );
        $config = new Zend_Config(array('section' => array()));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertNull(
            $newConfig->get('section')->get('a'),
            'IniWriter does not delete nested section properties'
        );
    }

    public function testWhetherSimplePropertiesOfExtendingSectionsAreInserted()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $config = new Zend_Config(
            array(
                'foo' => array('key1' => '1'),
                'bar' => array('key2' => '2')
            )
        );
        $config->setExtend('bar', 'foo');
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertInstanceOf(
            '\Zend_Config',
            $newConfig->get('foo'),
            'IniWriter does not insert extended sections'
        );
        $this->assertInstanceOf(
            '\Zend_Config',
            $newConfig->get('bar'),
            'IniWriter does not insert extending sections'
        );
        $this->assertEquals(
            '2',
            $newConfig->get('bar')->get('key2'),
            'IniWriter does not insert simple properties into extending sections'
        );
        $this->assertEquals(
            '1',
            $newConfig->get('foo')->get('key1'),
            'IniWriter does not properly define extending sections'
        );
    }

    /**
     * @depends testWhetherSimplePropertiesOfExtendingSectionsAreInserted
     */
    public function testWhetherSimplePropertiesOfExtendingSectionsAreUpdated()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[foo]
key1 = "1"

[bar : foo]
key2 = "2"
EOD
        );
        $config = new Zend_Config(
            array(
                'foo' => array('key1' => '1'),
                'bar' => array('key2' => '22')
            )
        );
        $config->setExtend('bar', 'foo');
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertEquals(
            '22',
            $newConfig->get('bar')->get('key2'),
            'IniWriter does not update simple properties of extending sections'
        );
    }

    /**
     * @depends testWhetherSimplePropertiesOfExtendingSectionsAreInserted
     */
    public function testWhetherSimplePropertiesOfExtendingSectionsAreDeleted()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[foo]
key1 = "1"

[bar : foo]
key2 = "2"
EOD
        );
        $config = new Zend_Config(
            array(
                'foo' => array('key1' => '1'),
                'bar' => array()
            )
        );
        $config->setExtend('bar', 'foo');
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertNull(
            $newConfig->get('bar')->get('key2'),
            'IniWriter does not delete simple properties of extending sections'
        );
    }

    public function testWhetherNestedPropertiesOfExtendingSectionsAreInserted()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $config = new Zend_Config(
            array(
                'foo' => array('a' => array('b' => 'c')),
                'bar' => array('d' => array('e' => 'f'))
            )
        );
        $config->setExtend('bar', 'foo');
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertInstanceOf(
            '\Zend_Config',
            $newConfig->get('foo'),
            'IniWriter does not insert extended sections'
        );
        $this->assertInstanceOf(
            '\Zend_Config',
            $newConfig->get('bar'),
            'IniWriter does not insert extending sections'
        );
        $this->assertInstanceOf(
            '\Zend_Config',
            $newConfig->get('bar')->get('d'),
            'IniWriter does not insert nested properties into extending sections'
        );
        $this->assertEquals(
            'f',
            $newConfig->get('bar')->get('d')->get('e'),
            'IniWriter does not insert nested properties into extending sections'
        );
        $this->assertEquals(
            'c',
            $newConfig->get('bar')->get('a')->get('b'),
            'IniWriter does not properly define extending sections with nested properties'
        );
    }

    /**
     * @depends testWhetherNestedPropertiesOfExtendingSectionsAreInserted
     */
    public function testWhetherNestedPropertiesOfExtendingSectionsAreUpdated()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[foo]
a.b = "c"

[bar : foo]
d.e = "f"
EOD
        );
        $config = new Zend_Config(
            array(
                'foo' => array('a' => array('b' => 'c')),
                'bar' => array('d' => array('e' => 'ff'))
            )
        );
        $config->setExtend('bar', 'foo');
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertEquals(
            'ff',
            $newConfig->get('bar')->get('d')->get('e'),
            'IniWriter does not update nested properties of extending sections'
        );
    }

    /**
     * @depends testWhetherNestedPropertiesOfExtendingSectionsAreInserted
     */
    public function testWhetherNestedPropertiesOfExtendingSectionsAreDeleted()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[foo]
a.b = "c"

[bar : foo]
d.e = "f"
EOD
        );
        $config = new Zend_Config(
            array(
                'foo' => array('a' => array('b' => 'c')),
                'bar' => array()
            )
        );
        $config->setExtend('bar', 'foo');
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = new Zend_Config_Ini($target);
        $this->assertNull(
            $newConfig->get('bar')->get('d'),
            'IniWriter does not delete nested properties of extending sections'
        );
    }

    public function testWhetherSectionOrderIsUpdated()
    {
        $config = <<<'EOD'
[one]
key1                = "1"
key2                = "2"


[two]
a.b                 = "c"
d.e                 = "f"


[three]
key                 = "value"
foo.bar             = "raboof"
EOD;

        $reverted = <<<'EOD'
[three]
key                 = "value"
foo.bar             = "raboof"


[two]
a.b                 = "c"
d.e                 = "f"


[one]
key1                = "1"
key2                = "2"
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array(
                'config'    => new Zend_Config(
                    array(
                        'three' => array(
                            'foo' => array(
                                'bar' => 'raboof'
                            ),
                            'key' => 'value'
                        ),
                        'two'   => array(
                            'd' => array(
                                'e' => 'f'
                            ),
                            'a' => array(
                                'b' => 'c'
                            )
                        ),
                        'one'   => array(
                            'key2' => '2',
                            'key1' => '1'
                        )
                    )
                ),
                'filename'  => $target
            )
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
            array(
                'config' => new Zend_Config(
                    array(
                        'two' => array(),
                        'one' => array()
                    )
                ),
                'filename'  => $target
            )
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
key                 = "value"
; another interesting comment
; boring comment
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array('config' => new Zend_Config(array('key' => 'value')), 'filename' => $target)
        );

        $this->assertEquals(
            $config,
            $writer->render(),
            'IniWriter does not preserve comments on empty lines'
        );
    }

    public function testWhetherCommentsOnPropertyLinesArePreserved()
    {
        $config = <<<'EOD'
foo                 = 1337                  ; I know what a " and a ' is
bar                 = 7331                  ; I; tend; to; overact; !1!1!!11!111!                                      ;
key                 = "value"               ; some comment for a small sized property
xxl                 = "very loooooooooooooooooooooong" ; my value is very lo...
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array(
                'config' => new Zend_Config(
                    array(
                        'foo' => 1337,
                        'bar' => 7331,
                        'key' => 'value',
                        'xxl' => 'very loooooooooooooooooooooong'
                    )
                ),
                'filename' => $target
            )
        );

        $this->assertEquals(
            $config,
            $writer->render(),
            'IniWriter does not preserve comments on property lines'
        );
    }

    public function testWhetherCommentsOnEmptySectionLinesArePreserved()
    {
        $config = <<<'EOD'
[section]
; some interesting comment, in a section
key                 = "value"
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array('config' => new Zend_Config(array('section' => array('key' => 'value'))), 'filename' => $target)
        );

        $this->assertEquals(
            $config,
            $writer->render(),
            'IniWriter does not preserve comments on empty section lines'
        );
    }

    public function testWhetherCommentsOnSectionPropertyLinesArePreserved()
    {
        $config = <<<'EOD'
[section]
foo                 = 1337                  ; I know what a " and a ' is
bar                 = 7331                  ; I; tend; to; overact; !1!1!!11!111!                                      ;
key                 = "value"               ; some comment for a small sized property
xxl                 = "very loooooooooooooooooooooong" ; my value is very lo...
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array(
                'config' => new Zend_Config(
                    array(
                        'section' => array(
                            'foo' => 1337,
                            'bar' => 7331,
                            'key' => 'value',
                            'xxl' => 'very loooooooooooooooooooooong'
                        )
                    )
                ),
                'filename' => $target
            )
        );

        $this->assertEquals(
            $config,
            $writer->render(),
            'IniWriter does not preserve comments on property lines'
        );
    }

    public function testKeyNormalization()
    {
        $normalKeys = new IniWriter(
            array (
                'config' => new Zend_Config(array (
                        'foo' => 'bar',
                        'nest' => array (
                            'nested' => array (
                                'stuff' => 'nested configuration element'
                            )
                        ),
                        'preserving' => array (
                            'ini' => array(
                                'writer' => 'n'
                            ),
                            'foo' => 'this should not be overwritten'
                        )
                 )),
                'filename' => $this->tempFile
            )

        );

        $nestedKeys = new IniWriter(
            array (
                'config' => new Zend_Config(array (
                    'foo' => 'bar',
                    'nest.nested.stuff' => 'nested configuration element',
                    'preserving.ini.writer' => 'n',
                    'preserving.foo' => 'this should not be overwritten'
                )),
                'filename' => $this->tempFile2
            )

        );
        $this->assertEquals($normalKeys->render(), $nestedKeys->render());
    }

    /**
     * Write a INI-configuration string to a temporary file and return it's path
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
}
