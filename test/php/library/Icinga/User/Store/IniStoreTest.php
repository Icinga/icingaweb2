<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\User\Preferences\Store;

use Mockery;
use Icinga\Data\ConfigObject;
use Icinga\Test\BaseTestCase;
use Icinga\User\Preferences\Store\IniStore;

class IniStoreWithSetGetPreferencesAndEmptyWrite extends IniStore
{
    public function write()
    {
        // Gets called by IniStore::save
    }

    public function setPreferences($preferences)
    {
        $this->preferences = $preferences;
    }

    public function getPreferences()
    {
        return $this->preferences;
    }
}

class IniStoreTest extends BaseTestCase
{
    public function testWhetherPreferenceChangesAreApplied()
    {
        $store = $this->getStore();
        $store->setPreferences(array('testsection' => array('key1' => '1')));

        $store->save(
            Mockery::mock('Icinga\User\Preferences', array(
                'toArray' => array('testsection' => array('key1' => '11', 'key2' => '2'))
            ))
        );
        $this->assertEquals(
            array('testsection' => array('key1' => '11', 'key2' => '2')),
            $store->getPreferences(),
            'IniStore::save does not properly apply changed preferences'
        );
    }

    public function testWhetherPreferenceDeletionsAreApplied()
    {
        $store = $this->getStore();
        $store->setPreferences(array('testsection' => array('key' => 'value')));

        $store->save(Mockery::mock('Icinga\User\Preferences', array('toArray' => array('testsection' => array()))));

        $result = $store->getPreferences();

        $this->assertEmpty($result['testsection'], 'IniStore::save does not delete removed preferences');
    }

    protected function getStore()
    {
        return new IniStoreWithSetGetPreferencesAndEmptyWrite(
            new ConfigObject(
                array(
                    'location' => 'some/Path/To/Some/Directory'
                )
            ),
            Mockery::mock('Icinga\User', array('getUsername' => 'unittest'))
        );
    }
}
