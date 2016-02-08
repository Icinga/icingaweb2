<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\User;

use Icinga\User\Preferences;
use Icinga\Test\BaseTestCase;

class PreferfencesTest extends BaseTestCase
{
    public function testWhetherPreferencesCanBeSet()
    {
        $prefs = new Preferences();

        $prefs->key = 'value';
        $this->assertTrue(isset($prefs->key));
        $this->assertEquals('value', $prefs->key);
    }

    public function testWhetherPreferencesCanBeAccessed()
    {
        $prefs = new Preferences(array('key' => 'value'));

        $this->assertTrue($prefs->has('key'));
        $this->assertEquals('value', $prefs->get('key'));
    }

    public function testWhetherPreferencesCanBeRemoved()
    {
        $prefs = new Preferences(array('key' => 'value'));

        unset($prefs->key);
        $this->assertFalse(isset($prefs->key));

        $prefs->key = 'value';
        $prefs->remove('key');
        $this->assertFalse($prefs->has('key'));
    }

    public function testWhetherPreferencesAreCountable()
    {
        $prefs = new Preferences(array('key1' => '1', 'key2' => '2'));

        $this->assertEquals(2, count($prefs));
    }

    public function testWhetherGetValueReturnsExpectedValue()
    {
        $prefs = new Preferences(array(
            'test' => array (
                'key1' => '1',
                'key2' => '2',
            )
        ));

        $result = $prefs->getValue('test', 'key2');

        $this->assertEquals('2', $result, 'Preferences::getValue() do not return an expected value');
    }
}
