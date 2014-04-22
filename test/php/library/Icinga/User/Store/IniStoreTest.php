<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\User\Preferences\Store;

use Mockery;
use Zend_Config;
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
        $store->setPreferences(array('key1' => '1'));

        $store->save(
            Mockery::mock('Icinga\User\Preferences', array('toArray' => array('key1' => '11', 'key2' => '2')))
        );
        $this->assertEquals(
            array('key1' => '11', 'key2' => '2'),
            $store->getPreferences(),
            'IniStore::save does not properly apply changed preferences'
        );
    }

    public function testWhetherPreferenceDeletionsAreApplied()
    {
        $store = $this->getStore();
        $store->setPreferences(array('key' => 'value'));

        $store->save(Mockery::mock('Icinga\User\Preferences', array('toArray' => array())));
        $this->assertEmpty($store->getPreferences(), 'IniStore::save does not delete removed preferences');
    }

    protected function getStore()
    {
        return new IniStoreWithSetGetPreferencesAndEmptyWrite(
            new Zend_Config(
                array(
                    'location' => 'some/Path/To/Some/Directory'
                )
            ),
            Mockery::mock('Icinga\User', array('getUsername' => 'unittest'))
        );
    }
}
