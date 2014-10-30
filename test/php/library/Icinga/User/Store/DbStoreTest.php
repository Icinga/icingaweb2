<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\User\Preferences\Store;

use Mockery;
use Exception;
use Zend_Config;
use Icinga\Test\BaseTestCase;
use Icinga\User\Preferences\Store\DbStore;

class DatabaseMock
{
    public $insertions = array();
    public $deletions = array();
    public $updates = array();

    public function quoteIdentifier($ident)
    {
        return $ident;
    }

    public function insert($table, $row)
    {
        $this->insertions[$row[DbStore::COLUMN_PREFERENCE]] = $row[DbStore::COLUMN_VALUE];
    }

    public function update($table, $columns, $where)
    {
        $this->updates[$where[DbStore::COLUMN_PREFERENCE . '=?']] = $columns[DbStore::COLUMN_VALUE];
    }

    public function delete($table, $where)
    {
        $this->deletions = array_merge(
            $this->deletions,
            $where[DbStore::COLUMN_PREFERENCE . ' IN (?)']
        );
    }
}

class FaultyDatabaseMock extends DatabaseMock
{
    public function insert($table, $row)
    {
        throw new Exception();
    }

    public function update($table, $columns, $where)
    {
        throw new Exception();
    }

    public function delete($table, $where)
    {
        throw new Exception();
    }
}

class DbStoreWithSetPreferences extends DbStore
{
    public function setPreferences(array $preferences)
    {
        $this->preferences = $preferences;
    }
}

class DbStoreTest extends BaseTestCase
{
    public function testWhetherPreferenceInsertionWorks()
    {
        $dbMock = new DatabaseMock();
        $store = $this->getStore($dbMock);
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                array('toArray' => array('key' => 'value'))
            )
        );

        $this->assertArrayHasKey('key', $dbMock->insertions, 'DbStore::save does not insert new preferences');
        $this->assertEmpty($dbMock->updates, 'DbStore::save updates *new* preferences');
        $this->assertEmpty($dbMock->deletions, 'DbStore::save deletes *new* preferences');
    }

    /**
     * @expectedException   Icinga\Exception\NotWritableError
     */
    public function testWhetherPreferenceInsertionThrowsNotWritableError()
    {
        $store = $this->getStore(new FaultyDatabaseMock());
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                array('toArray' => array('key' => 'value'))
            )
        );
    }

    public function testWhetherPreferenceUpdatesWork()
    {
        $dbMock = new DatabaseMock();
        $store = $this->getStore($dbMock);
        $store->setPreferences(array('key' => 'value'));
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                array('toArray' => array('key' => 'eulav'))
            )
        );

        $this->assertArrayHasKey('key', $dbMock->updates, 'DbStore::save does not update existing preferences');
        $this->assertEmpty($dbMock->insertions, 'DbStore::save inserts *existing* preferences');
        $this->assertEmpty($dbMock->deletions, 'DbStore::save inserts *existing* preferneces');
    }

    /**
     * @expectedException   Icinga\Exception\NotWritableError
     */
    public function testWhetherPreferenceUpdatesThrowNotWritableError()
    {
        $store = $this->getStore(new FaultyDatabaseMock());
        $store->setPreferences(array('key' => 'value'));
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                array('toArray' => array('key' => 'eulav'))
            )
        );
    }

    public function testWhetherPreferenceDeletionWorks()
    {
        $dbMock = new DatabaseMock();
        $store = $this->getStore($dbMock);
        $store->setPreferences(array('key' => 'value'));
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                array('toArray' => array())
            )
        );

        $this->assertContains('key', $dbMock->deletions, 'DbStore::save does not delete removed preferences');
        $this->assertEmpty($dbMock->insertions, 'DbStore::save inserts *removed* preferences');
        $this->assertEmpty($dbMock->updates, 'DbStore::save updates *removed* preferences');
    }

    /**
     * @expectedException   Icinga\Exception\NotWritableError
     */
    public function testWhetherPreferenceDeletionThrowsNotWritableError()
    {
        $store = $this->getStore(new FaultyDatabaseMock());
        $store->setPreferences(array('key' => 'value'));
        $store->save(
            Mockery::mock(
                'Icinga\User\Preferences',
                array('toArray' => array())
            )
        );
    }

    protected function getStore($dbMock)
    {
        return new DbStoreWithSetPreferences(
            new Zend_Config(
                array(
                    'connection' => Mockery::mock(array('getDbAdapter' => $dbMock))
                )
            ),
            Mockery::mock('Icinga\User', array('getUsername' => 'unittest'))
        );
    }
}
