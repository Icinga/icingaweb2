<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Application;

use Icinga\Test\BaseTestCase;
use Icinga\Application\Config;

class ConfigTest extends BaseTestCase
{
    /**
     * Set up config dir
     */
    public function setUp()
    {
        parent::setUp();
        $this->oldConfigDir = Config::$configDir;
        Config::$configDir = dirname(__FILE__) . '/ConfigTest/files';
    }

    /**
     * Reset config dir
     */
    public function tearDown()
    {
        parent::tearDown();
        Config::$configDir = $this->oldConfigDir;
    }

    public function testWhetherInitializingAConfigWithAssociativeArraysCreatesHierarchicalConfigObjects()
    {
        $config = new Config(array(
            'a' => 'b',
            'c' => 'd',
            'e' => array(
                'f' => 'g',
                'h' => 'i',
                'j' => array(
                    'k' => 'l',
                    'm' => 'n'
                )
            )
        ));

        $this->assertInstanceOf(
            get_class($config),
            $config->e,
            'Config::__construct() does not accept two dimensional arrays'
        );
        $this->assertInstanceOf(
            get_class($config),
            $config->e->j,
            'Config::__construct() does not accept multi dimensional arrays'
        );
    }

    /**
     * @depends testWhetherInitializingAConfigWithAssociativeArraysCreatesHierarchicalConfigObjects
     */
    public function testWhetherItIsPossibleToCloneConfigObjects()
    {
        $config = new Config(array(
            'a' => 'b',
            'c' => array(
                'd' => 'e'
            )
        ));
        $newConfig = clone $config;

        $this->assertNotSame(
            $config,
            $newConfig,
            'Shallow cloning objects of type Config does not seem to work properly'
        );
        $this->assertNotSame(
            $config->c,
            $newConfig->c,
            'Deep cloning objects of type Config does not seem to work properly'
        );
    }

    public function testWhetherConfigObjectsAreCountable()
    {
        $config = new Config(array('a' => 'b', 'c' => array('d' => 'e')));

        $this->assertInstanceOf('Countable', $config, 'Config objects do not implement interface `Countable\'');
        $this->assertEquals(2, count($config), 'Config objects do not count properties and sections correctly');
    }

    public function testWhetherConfigObjectsAreTraversable()
    {
        $config = new Config(array('a' => 'b', 'c' => 'd'));
        $config->e = 'f';

        $this->assertInstanceOf('Iterator', $config, 'Config objects do not implement interface `Iterator\'');

        $actual = array();
        foreach ($config as $key => $value) {
            $actual[$key] = $value;
        }

        $this->assertEquals(
            array('a' => 'b', 'c' => 'd', 'e' => 'f'),
            $actual,
            'Config objects do not iterate properly in the order their values were inserted'
        );
    }

    public function testWhetherOneCanCheckWhetherConfigObjectsHaveACertainPropertyOrSection()
    {
        $config = new Config(array('a' => 'b', 'c' => array('d' => 'e')));

        $this->assertTrue(isset($config->a), 'Config objects do not seem to implement __isset() properly');
        $this->assertTrue(isset($config->c->d), 'Config objects do not seem to implement __isset() properly');
        $this->assertFalse(isset($config->d), 'Config objects do not seem to implement __isset() properly');
        $this->assertFalse(isset($config->c->e), 'Config objects do not seem to implement __isset() properly');
        $this->assertTrue(isset($config['a']), 'Config object do not seem to implement offsetExists() properly');
        $this->assertFalse(isset($config['d']), 'Config object do not seem to implement offsetExists() properly');
    }

    public function testWhetherItIsPossibleToAccessProperties()
    {
        $config = new Config(array('a' => 'b', 'c' => null));

        $this->assertEquals('b', $config->a, 'Config objects do not allow property access');
        $this->assertNull($config['c'], 'Config objects do not allow offset access');
        $this->assertNull($config->d, 'Config objects do not return NULL as default');
    }

    public function testWhetherItIsPossibleToSetPropertiesAndSections()
    {
        $config = new Config();
        $config->a = 'b';
        $config['c'] = array('d' => 'e');

        $this->assertTrue(isset($config->a), 'Config objects do not allow to set properties');
        $this->assertTrue(isset($config->c), 'Config objects do not allow to set offsets');
        $this->assertInstanceOf(
            get_class($config),
            $config->c,
            'Config objects do not convert arrays to config objects when set'
        );
    }

    /**
     * @expectedException LogicException
     */
    public function testWhetherItIsNotPossibleToAppendProperties()
    {
        $config = new Config();
        $config[] = 'test';
    }

    public function testWhetherItIsPossibleToUnsetPropertiesAndSections()
    {
        $config = new Config(array('a' => 'b', 'c' => array('d' => 'e')));
        unset($config->a);
        unset($config['c']);

        $this->assertFalse(isset($config->a), 'Config objects do not allow to unset properties');
        $this->assertFalse(isset($config->c), 'Config objects do not allow to unset sections');
    }

    /**
     * @depends testWhetherConfigObjectsAreCountable
     */
    public function testWhetherOneCanCheckIfAConfigObjectHasAnyPropertiesOrSections()
    {
        $config = new Config();
        $this->assertTrue($config->isEmpty(), 'Config objects do not report that they are empty');

        $config->test = 'test';
        $this->assertFalse($config->isEmpty(), 'Config objects do report that they are empty although they are not');
    }

    /**
     * @depends testWhetherItIsPossibleToAccessProperties
     */
    public function testWhetherItIsPossibleToRetrieveDefaultValuesForNonExistentPropertiesOrSections()
    {
        $config = new Config(array('a' => 'b'));

        $this->assertEquals(
            'b',
            $config->get('a'),
            'Config objects do not return the actual value of existing properties'
        );
        $this->assertNull(
            $config->get('b'),
            'Config objects do not return NULL as default for non-existent properties'
        );
        $this->assertEquals(
            'test',
            $config->get('test', 'test'),
            'Config objects do not allow to define the default value to return for non-existent properties'
        );
    }

    public function testWhetherItIsPossibleToRetrieveAllPropertyAndSectionNames()
    {
        $config = new Config(array('a' => 'b', 'c' => array('d' => 'e')));

        $this->assertEquals(
            array('a', 'c'),
            $config->keys(),
            'Config objects do not list property and section names correctly'
        );
    }

    public function testWhetherConfigObjectsCanBeConvertedToArrays()
    {
        $config = new Config(array('a' => 'b', 'c' => array('d' => 'e')));

        $this->assertEquals(
            array('a' => 'b', 'c' => array('d' => 'e')),
            $config->toArray(),
            'Config objects cannot be correctly converted to arrays'
        );
    }

    /**
     * @depends testWhetherConfigObjectsCanBeConvertedToArrays
     */
    public function testWhetherItIsPossibleToMergeConfigObjects()
    {
        $config = new Config(array('a' => 'b'));

        $config->merge(array('a' => 'bb', 'c' => 'd', 'e' => array('f' => 'g')));
        $this->assertEquals(
            array('a' => 'bb', 'c' => 'd', 'e' => array('f' => 'g')),
            $config->toArray(),
            'Config objects cannot be extended with arrays'
        );

        $config->merge(new Config(array('c' => array('d' => 'ee'), 'e' => array('h' => 'i'))));
        $this->assertEquals(
            array('a' => 'bb', 'c' => array('d' => 'ee'), 'e' => array('f' => 'g', 'h' => 'i')),
            $config->toArray(),
            'Config objects cannot be extended with other Config objects'
        );
    }

    /**
     * @depends testWhetherItIsPossibleToAccessProperties
     */
    public function testWhetherItIsPossibleToDirectlyRetrieveASectionProperty()
    {
        $config = new Config(array('a' => array('b' => 'c')));

        $this->assertEquals(
            'c',
            $config->fromSection('a', 'b'),
            'Config::fromSection does not return the actual value of a section\'s property'
        );
        $this->assertNull(
            $config->fromSection('a', 'c'),
            'Config::fromSection does not return NULL as default for non-existent section properties'
        );
        $this->assertNull(
            $config->fromSection('b', 'c'),
            'Config::fromSection does not return NULL as default for non-existent sections'
        );
        $this->assertEquals(
            'test',
            $config->fromSection('a', 'c', 'test'),
            'Config::fromSection does not return the given default value for non-existent section properties'
        );
    }

    /**
     * @expectedException UnexpectedValueException
     * @depends testWhetherItIsPossibleToAccessProperties
     */
    public function testWhetherAnExceptionIsThrownWhenTryingToAccessASectionPropertyOnANonSection()
    {
        $config = new Config(array('a' => 'b'));
        $config->fromSection('a', 'b');
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
     * @depends testWhetherConfigObjectsCanBeConvertedToArrays
     * @depends testWhetherConfigResolvePathReturnsValidAbsolutePaths
     */
    public function testWhetherItIsPossibleToInitializeAConfigObjectFromAIniFile()
    {
        $config = Config::fromIni(Config::resolvePath('config.ini'));

        $this->assertEquals(
            array(
                'logging' => array(
                    'enable'    => 1
                ),
                'backend' => array(
                    'type'      => 'db',
                    'user'      => 'user',
                    'password'  => 'password',
                    'disable'   => 1
                )
            ),
            $config->toArray(),
            'Config::fromIni does not load INI files correctly'
        );

        $this->assertInstanceOf(
            get_class($config),
            Config::fromIni('nichda'),
            'Config::fromIni does not return empty configs for non-existent configuration files'
        );
    }

    /**
     * @expectedException Icinga\Exception\NotReadableError
     */
    public function testWhetherFromIniThrowsAnExceptionOnInsufficientPermission()
    {
        Config::fromIni('/etc/shadow');
    }

    /**
     * @depends testWhetherItIsPossibleToInitializeAConfigObjectFromAIniFile
     */
    public function testWhetherItIsPossibleToRetrieveApplicationConfiguration()
    {
        $config = Config::app();

        $this->assertEquals(
            array(
                'logging' => array(
                    'enable'    => 1
                ),
                'backend' => array(
                    'type'      => 'db',
                    'user'      => 'user',
                    'password'  => 'password',
                    'disable'   => 1
                )
            ),
            $config->toArray(),
            'Config::app does not load INI files correctly'
        );
    }

    /**
     * @depends testWhetherItIsPossibleToInitializeAConfigObjectFromAIniFile
     */
    public function testWhetherItIsPossibleToRetrieveModuleConfiguration()
    {
        $config = Config::module('amodule');

        $this->assertEquals(
            array(
                'menu' => array(
                    'breadcrumb' => 1
                )
            ),
            $config->toArray(),
            'Config::module does not load INI files correctly'
        );
    }
}
