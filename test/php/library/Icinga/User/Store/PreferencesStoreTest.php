<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\User\Preferences;

use Icinga\User\Preferences\PreferencesStore;
use Mockery;
use Icinga\Data\ConfigObject;
use Icinga\Exception\NotWritableError;
use Icinga\Test\BaseTestCase;

class DatabaseMock
{
    public $insertions = [];
    public $deletions = [];
    public $updates = [];

    public function quoteIdentifier($ident)
    {
        return $ident;
    }

    public function insert($table, $row)
    {
        $this->insertions[$row[PreferencesStore::COLUMN_PREFERENCE]] = $row[PreferencesStore::COLUMN_VALUE];
    }

    public function update($table, $columns, $where)
    {
        $this->updates[$where[PreferencesStore::COLUMN_PREFERENCE . '=?']] = $columns[PreferencesStore::COLUMN_VALUE];
    }

    public function delete($table, $where)
    {
        $this->deletions = array_merge(
            $this->deletions,
            $where[PreferencesStore::COLUMN_PREFERENCE . ' IN (?)']
        );
    }
}

class FaultyDatabaseMock extends DatabaseMock
{
    public function insert($table, $row)
    {
        throw new NotWritableError('Mocked insert');
    }

    public function update($table, $columns, $where)
    {
        throw new NotWritableError('Mocked update');
    }

    public function delete($table, $where)
    {
        throw new NotWritableError('Mocked delete');
    }
}

class PreferencesStoreWithSetPreferences extends PreferencesStore
{
    public function setPreferences(array $preferences)
    {
        $this->preferences = $preferences;
    }
}

class PreferencesStoreTest extends BaseTestCase
{
    public function testWhetherPreferenceInsertionWorks()
    {
        $dbMock = new DatabaseMock();
        $store = $this->getStore($dbMock);
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                ['toArray' => ['testsection' => ['key' => 'value']]]
            )
        );

        $this->assertArrayHasKey('key', $dbMock->insertions, 'PreferencesStore::save does not insert new preferences');
        $this->assertEmpty($dbMock->updates, 'PreferencesStore::save updates *new* preferences');
        $this->assertEmpty($dbMock->deletions, 'PreferencesStore::save deletes *new* preferences');
    }

    public function testWhetherPreferenceInsertionThrowsNotWritableError()
    {
        $this->expectException(\Icinga\Exception\NotWritableError::class);

        $store = $this->getStore(new FaultyDatabaseMock());
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                ['toArray' => ['testsection' => ['key' => 'value']]]
            )
        );
    }

    public function testWhetherPreferenceUpdatesWork()
    {
        $dbMock = new DatabaseMock();
        $store = $this->getStore($dbMock);
        $store->setPreferences(['testsection' => ['key' => 'value']]);
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                ['toArray' => ['testsection' => ['key' => 'eulav']]]
            )
        );

        $this->assertArrayHasKey('key', $dbMock->updates, 'PreferencesStore::save does not update existing preferences');
        $this->assertEmpty($dbMock->insertions, 'PreferencesStore::save inserts *existing* preferences');
        $this->assertEmpty($dbMock->deletions, 'PreferencesStore::save inserts *existing* preferneces');
    }

    public function testWhetherPreferenceUpdatesThrowNotWritableError()
    {
        $this->expectException(\Icinga\Exception\NotWritableError::class);

        $store = $this->getStore(new FaultyDatabaseMock());
        $store->setPreferences(['testsection' => ['key' => 'value']]);
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                ['toArray' => ['testsection' => ['key' => 'eulav']]]
            )
        );
    }

    public function testWhetherPreferenceDeletionWorks()
    {
        $dbMock = new DatabaseMock();
        $store = $this->getStore($dbMock);
        $store->setPreferences(['testsection' => ['key' => 'value']]);
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                ['toArray' => ['testsection' => []]]
            )
        );

        $this->assertContains('key', $dbMock->deletions, 'PreferencesStore::save does not delete removed preferences');
        $this->assertEmpty($dbMock->insertions, 'PreferencesStore::save inserts *removed* preferences');
        $this->assertEmpty($dbMock->updates, 'PreferencesStore::save updates *removed* preferences');
    }

    public function testWhetherPreferenceDeletionThrowsNotWritableError()
    {
        $this->expectException(\Icinga\Exception\NotWritableError::class);

        $store = $this->getStore(new FaultyDatabaseMock());
        $store->setPreferences(['testsection' => ['key' => 'value']]);
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                ['toArray' => ['testsection' => ['key' => 'foo']]]
            )
        );
    }

    protected function getStore($dbMock)
    {
        return new PreferencesStoreWithSetPreferences(
            new ConfigObject(
                [
                    'connection' => Mockery::mock(['getDbAdapter' => $dbMock])
                ]
            ),
            Mockery::mock('Icinga\User', ['getUsername' => 'unittest'])
        );
    }
}
