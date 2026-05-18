<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\User;

use Icinga\User\Preferences;
use Icinga\Test\BaseTestCase;

class PreferencesTest extends BaseTestCase
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
        $prefs = new Preferences(['key' => 'value']);

        $this->assertTrue($prefs->has('key'));
        $this->assertEquals('value', $prefs->get('key'));
    }

    public function testWhetherPreferencesCanBeRemoved()
    {
        $prefs = new Preferences(['key' => 'value']);

        unset($prefs->key);
        $this->assertFalse(isset($prefs->key));

        $prefs->key = 'value';
        $prefs->remove('key');
        $this->assertFalse($prefs->has('key'));
    }

    public function testWhetherPreferencesAreCountable()
    {
        $prefs = new Preferences(['key1' => '1', 'key2' => '2']);

        $this->assertEquals(2, count($prefs));
    }

    public function testWhetherGetValueReturnsExpectedValue()
    {
        $prefs = new Preferences([
            'test' => [
                'key1' => '1',
                'key2' => '2',
            ]
        ]);

        $result = $prefs->getValue('test', 'key2');

        $this->assertEquals('2', $result, 'Preferences::getValue() do not return an expected value');
    }
}
