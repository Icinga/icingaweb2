<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Data;

use Icinga\Test\BaseTestCase;
use Icinga\Data\ConfigObject;

class ConfigObjectTest extends BaseTestCase
{
    public function testWhetherInitializingAConfigWithAssociativeArraysCreatesHierarchicalConfigObjects()
    {
        $config = new ConfigObject(array(
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
            'ConfigObject::__construct() does not accept two dimensional arrays'
        );
        $this->assertInstanceOf(
            get_class($config),
            $config->e->j,
            'ConfigObject::__construct() does not accept multi dimensional arrays'
        );
    }

    /**
     * @depends testWhetherInitializingAConfigWithAssociativeArraysCreatesHierarchicalConfigObjects
     */
    public function testWhetherItIsPossibleToCloneConfigObjects()
    {
        $config = new ConfigObject(array(
            'a' => 'b',
            'c' => array(
                'd' => 'e'
            )
        ));
        $newConfig = clone $config;

        $this->assertNotSame(
            $config,
            $newConfig,
            'Shallow cloning objects of type ConfigObject does not seem to work properly'
        );
        $this->assertNotSame(
            $config->c,
            $newConfig->c,
            'Deep cloning objects of type ConfigObject does not seem to work properly'
        );
    }

    public function testWhetherConfigObjectsAreTraversable()
    {
        $config = new ConfigObject(array('a' => 'b', 'c' => 'd'));
        $config->e = 'f';

        $this->assertInstanceOf('Iterator', $config, 'ConfigObject objects do not implement interface `Iterator\'');

        $actual = array();
        foreach ($config as $key => $value) {
            $actual[$key] = $value;
        }

        $this->assertEquals(
            array('a' => 'b', 'c' => 'd', 'e' => 'f'),
            $actual,
            'ConfigObject objects do not iterate properly in the order their values were inserted'
        );
    }

    public function testWhetherOneCanCheckWhetherConfigObjectsHaveACertainPropertyOrSection()
    {
        $config = new ConfigObject(array('a' => 'b', 'c' => array('d' => 'e')));

        $this->assertTrue(isset($config->a), 'ConfigObjects do not seem to implement __isset() properly');
        $this->assertTrue(isset($config->c->d), 'ConfigObjects do not seem to implement __isset() properly');
        $this->assertFalse(isset($config->d), 'ConfigObjects do not seem to implement __isset() properly');
        $this->assertFalse(isset($config->c->e), 'ConfigObjects do not seem to implement __isset() properly');
        $this->assertTrue(isset($config['a']), 'ConfigObject do not seem to implement offsetExists() properly');
        $this->assertFalse(isset($config['d']), 'ConfigObject do not seem to implement offsetExists() properly');
    }

    public function testWhetherItIsPossibleToAccessProperties()
    {
        $config = new ConfigObject(array('a' => 'b', 'c' => null));

        $this->assertEquals('b', $config->a, 'ConfigObjects do not allow property access');
        $this->assertNull($config['c'], 'ConfigObjects do not allow offset access');
        $this->assertNull($config->d, 'ConfigObjects do not return NULL as default');
    }

    public function testWhetherItIsPossibleToSetPropertiesAndSections()
    {
        $config = new ConfigObject();
        $config->a = 'b';
        $config['c'] = array('d' => 'e');

        $this->assertTrue(isset($config->a), 'ConfigObjects do not allow to set properties');
        $this->assertTrue(isset($config->c), 'ConfigObjects do not allow to set offsets');
        $this->assertInstanceOf(
            get_class($config),
            $config->c,
            'ConfigObjects do not convert arrays to config objects when set'
        );
    }

    /**
     * @expectedException \Icinga\Exception\ProgrammingError
     */
    public function testWhetherItIsNotPossibleToAppendProperties()
    {
        $config = new ConfigObject();
        $config[] = 'test';
    }

    public function testWhetherItIsPossibleToUnsetPropertiesAndSections()
    {
        $config = new ConfigObject(array('a' => 'b', 'c' => array('d' => 'e')));
        unset($config->a);
        unset($config['c']);

        $this->assertFalse(isset($config->a), 'ConfigObjects do not allow to unset properties');
        $this->assertFalse(isset($config->c), 'ConfigObjects do not allow to unset sections');
    }

    public function testWhetherOneCanCheckIfAConfigObjectHasAnyPropertiesOrSections()
    {
        $config = new ConfigObject();
        $this->assertTrue($config->isEmpty(), 'ConfigObjects do not report that they are empty');

        $config->test = 'test';
        $this->assertFalse($config->isEmpty(), 'ConfigObjects do report that they are empty although they are not');
    }

    /**
     * @depends testWhetherItIsPossibleToAccessProperties
     */
    public function testWhetherItIsPossibleToRetrieveDefaultValuesForNonExistentPropertiesOrSections()
    {
        $config = new ConfigObject(array('a' => 'b'));

        $this->assertEquals(
            'b',
            $config->get('a'),
            'ConfigObjects do not return the actual value of existing properties'
        );
        $this->assertNull(
            $config->get('b'),
            'ConfigObjects do not return NULL as default for non-existent properties'
        );
        $this->assertEquals(
            'test',
            $config->get('test', 'test'),
            'ConfigObjects do not allow to define the default value to return for non-existent properties'
        );
    }

    public function testWhetherItIsPossibleToRetrieveAllPropertyAndSectionNames()
    {
        $config = new ConfigObject(array('a' => 'b', 'c' => array('d' => 'e')));

        $this->assertEquals(
            array('a', 'c'),
            $config->keys(),
            'ConfigObjects do not list property and section names correctly'
        );
    }

    public function testWhetherConfigObjectsCanBeConvertedToArrays()
    {
        $config = new ConfigObject(array('a' => 'b', 'c' => array('d' => 'e')));

        $this->assertEquals(
            array('a' => 'b', 'c' => array('d' => 'e')),
            $config->toArray(),
            'ConfigObjects cannot be correctly converted to arrays'
        );
    }

    /**
     * @depends testWhetherConfigObjectsCanBeConvertedToArrays
     */
    public function testWhetherItIsPossibleToMergeConfigObjects()
    {
        $config = new ConfigObject(array('a' => 'b'));

        $config->merge(array('a' => 'bb', 'c' => 'd', 'e' => array('f' => 'g')));
        $this->assertEquals(
            array('a' => 'bb', 'c' => 'd', 'e' => array('f' => 'g')),
            $config->toArray(),
            'ConfigObjects cannot be extended with arrays'
        );

        $config->merge(new ConfigObject(array('c' => array('d' => 'ee'), 'e' => array('h' => 'i'))));
        $this->assertEquals(
            array('a' => 'bb', 'c' => array('d' => 'ee'), 'e' => array('f' => 'g', 'h' => 'i')),
            $config->toArray(),
            'ConfigObjects cannot be extended with other ConfigObjects'
        );
    }
}
