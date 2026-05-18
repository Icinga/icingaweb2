<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Application;

use Icinga\Test\BaseTestCase;
use Icinga\Application\Config;

class ConfigTest extends BaseTestCase
{
    /**
     * Old config dir
     *
     * @var string
     */
    protected $oldConfigDir;

    /**
     * Set up config dir
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->oldConfigDir = Config::$configDir;
        Config::$configDir = dirname(__FILE__) . '/ConfigTest/files';
    }

    /**
     * Reset config dir
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Config::$configDir = $this->oldConfigDir;
    }

    public function testWhetherConfigIsCountable()
    {
        $config = Config::fromArray(['a' => 'b', 'c' => ['d' => 'e']]);

        $this->assertInstanceOf('Countable', $config, 'Config does not implement interface `Countable\'');
        $this->assertEquals(2, count($config), 'Config does not count sections correctly');
    }

    public function testWhetherConfigIsTraversable()
    {
        $config = Config::fromArray(['a' => [], 'c' => []]);
        $config->setSection('e');

        $this->assertInstanceOf('Iterator', $config, 'Config does not implement interface `Iterator\'');

        $actual = [];
        foreach ($config as $key => $_) {
            $actual[] = $key;
        }

        $this->assertEquals(
            ['a', 'c', 'e'],
            $actual,
            'Config does not iterate properly in the order its sections were inserted'
        );
    }

    public function testWhetherOneCanCheckIfAConfigHasAnySections()
    {
        $config = new Config();
        $this->assertTrue($config->isEmpty(), 'Config does not report that it is empty');

        $config->setSection('test');
        $this->assertFalse($config->isEmpty(), 'Config does report that it is empty although it is not');
    }

    public function testWhetherItIsPossibleToRetrieveAllSectionNames()
    {
        $config = Config::fromArray(['a' => ['b' => 'c'], 'd' => ['e' => 'f']]);

        $this->assertEquals(
            ['a', 'd'],
            $config->keys(),
            'Config::keys does not list section names correctly'
        );
    }

    public function testWhetherConfigCanBeConvertedToAnArray()
    {
        $config = Config::fromArray(['a' => 'b', 'c' => ['d' => 'e']]);

        $this->assertEquals(
            ['a' => 'b', 'c' => ['d' => 'e']],
            $config->toArray(),
            'Config::toArray does not return the correct array'
        );
    }

    public function testWhetherItIsPossibleToDirectlyRetrieveASectionProperty()
    {
        $config = Config::fromArray(['a' => ['b' => 'c']]);

        $this->assertEquals(
            'c',
            $config->get('a', 'b'),
            'Config::get does not return the actual value of a section\'s property'
        );
        $this->assertNull(
            $config->get('a', 'c'),
            'Config::get does not return NULL as default for non-existent section properties'
        );
        $this->assertNull(
            $config->get('b', 'c'),
            'Config::get does not return NULL as default for non-existent sections'
        );
        $this->assertEquals(
            'test',
            $config->get('a', 'c', 'test'),
            'Config::get does not return the given default value for non-existent section properties'
        );
        $this->assertEquals(
            'c',
            $config->get('a', 'b', 'test'),
            'Config::get does not return the actual value of a section\'s property in case a default is given'
        );
    }

    public function testWhetherConfigReturnsSingleSections()
    {
        $config = Config::fromArray(['a' => ['b' => 'c']]);

        $this->assertInstanceOf(
            'Icinga\Data\ConfigObject',
            $config->getSection('a'),
            'Config::getSection does not return a known section'
        );
    }

    /**
     * @depends testWhetherConfigReturnsSingleSections
     */
    public function testWhetherConfigSetsSingleSections()
    {
        $config = new Config();
        $config->setSection('a', ['b' => 'c']);

        $this->assertInstanceOf(
            'Icinga\Data\ConfigObject',
            $config->getSection('a'),
            'Config::setSection does not set a new section'
        );

        $config->setSection('a', ['bb' => 'cc']);

        $this->assertNull(
            $config->getSection('a')->b,
            'Config::setSection does not overwrite existing sections'
        );
        $this->assertEquals(
            'cc',
            $config->getSection('a')->bb,
            'Config::setSection does not overwrite existing sections'
        );
    }

    /**
     * @depends testWhetherConfigIsCountable
     */
    public function testWhetherConfigRemovesSingleSections()
    {
        $config = Config::fromArray(['a' => ['b' => 'c'], 'd' => ['e' => 'f']]);
        $config->removeSection('a');

        $this->assertEquals(
            1,
            $config->count(),
            'Config::removeSection does not remove a known section'
        );
    }

    /**
     * @depends testWhetherConfigSetsSingleSections
     */
    public function testWhetherConfigKnowsWhichSectionsItHas()
    {
        $config = new Config();
        $config->setSection('a');

        $this->assertTrue(
            $config->hasSection('a'),
            'Config::hasSection does not know anything about its sections'
        );
        $this->assertFalse(
            $config->hasSection('b'),
            'Config::hasSection does not know anything about its sections'
        );
    }

    public function testWhetherAnExceptionIsThrownWhenTryingToAccessASectionPropertyOnANonSection()
    {
        $this->expectException(\UnexpectedValueException::class);

        $config = Config::fromArray(['a' => 'b']);
        $config->get('a', 'b');
    }

    public function testWhetherConfigResolvePathReturnsValidAbsolutePaths()
    {
        $this->assertEquals(
            Config::$configDir . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b.ini',
            Config::resolvePath(DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b.ini'),
            'Config::resolvePath does not produce valid absolute paths'
        );
    }

    /**
     * @depends testWhetherConfigCanBeConvertedToAnArray
     * @depends testWhetherConfigResolvePathReturnsValidAbsolutePaths
     */
    public function testWhetherItIsPossibleToInitializeAConfigFromAIniFile()
    {
        $config = Config::fromIni(Config::resolvePath('config.ini'));

        $this->assertEquals(
            [
                'logging' => [
                    'enable'    => 1
                ],
                'backend' => [
                    'type'      => 'db',
                    'user'      => 'user',
                    'password'  => 'password',
                    'disable'   => 1
                ]
            ],
            $config->toArray(),
            'Config::fromIni does not load INI files correctly'
        );

        $this->assertInstanceOf(
            get_class($config),
            Config::fromIni('nichda'),
            'Config::fromIni does not return empty configs for non-existent configuration files'
        );
    }

    public function testWhetherFromIniThrowsAnExceptionOnInsufficientPermission()
    {
        $this->expectException(\Icinga\Exception\NotReadableError::class);

        Config::fromIni('/etc/shadow');
    }

    /**
     * @depends testWhetherItIsPossibleToInitializeAConfigFromAIniFile
     */
    public function testWhetherItIsPossibleToRetrieveApplicationConfiguration()
    {
        $config = Config::app('config', true);

        $this->assertEquals(
            [
                'logging' => [
                    'enable'    => 1
                ],
                'backend' => [
                    'type'      => 'db',
                    'user'      => 'user',
                    'password'  => 'password',
                    'disable'   => 1
                ]
            ],
            $config->toArray(),
            'Config::app does not load INI files correctly'
        );
    }

    /**
     * @depends testWhetherItIsPossibleToInitializeAConfigFromAIniFile
     */
    public function testWhetherItIsPossibleToRetrieveModuleConfiguration()
    {
        $config = Config::module('amodule');

        $this->assertEquals(
            [
                'menu' => [
                    'breadcrumb' => 1
                ]
            ],
            $config->toArray(),
            'Config::module does not load INI files correctly'
        );
    }
}
