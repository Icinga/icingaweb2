<?php

namespace Tests\Icinga\User;

require_once __DIR__. '/../../../../../library/Icinga/User/Preferences.php';
require_once __DIR__. '/../../../../../library/Icinga/Exception/ProgrammingError.php';

use \PHPUnit_Framework_TestCase;
use Icinga\User\Preferences;

class PreferencesTest extends PHPUnit_Framework_TestCase
{
    public function testInitialPreferences()
    {
        $preferences = new Preferences(
            array(
                'test.key1' => 'ok1',
                'test.key2' => 'ok2'
            )
        );

        $this->assertCount(2, $preferences);

        $this->assertEquals('ok2', $preferences->get('test.key2'));
    }

    public function testGetDefaultValues()
    {
        $preferences = new Preferences(array());
        $preferences->set('test.key223', 'ok223');
        $preferences->set('test.key333', 'ok333');

        $this->assertCount(2, $preferences);

        $this->assertEquals('ok223', $preferences->get('test.key223'));

        $this->assertNull($preferences->get('does.not.exist'));

        $this->assertEquals(123123, $preferences->get('does.not.exist', 123123));
    }

    public function testTransactionalCommit()
    {
        $preferences = new Preferences(array());
        $preferences->startTransaction();

        $preferences->set('test.key1', 'ok1');
        $preferences->set('test.key2', 'ok2');

        $this->assertCount(0, $preferences);
        $this->assertCount(2, $preferences->getChangeSet()->getCreate());

        $preferences->commit();

        $this->assertCount(2, $preferences);

        $preferences->startTransaction();

        $preferences->remove('test.key2');
        $this->assertEquals('ok2', $preferences->get('test.key2'));
        $this->assertCount(1, $preferences->getChangeSet()->getDelete());

        $preferences->commit();
        $this->assertNull($preferences->get('test.key2'));
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage Nothing to commit
     */
    public function testNothingToCommitException()
    {
        $preferences = new Preferences(array());
        $preferences->commit();
    }

    public function testSetCreateOrUpdate()
    {
        $preferences = new Preferences(array());

        $preferences->startTransaction();
        $preferences->set('test.key1', 'ok1');
        $this->assertCount(1, $preferences->getChangeSet()->getCreate());
        $this->assertCount(0, $preferences->getChangeSet()->getUpdate());
        $preferences->commit();

        $preferences->startTransaction();
        $preferences->set('test.key1', 'ok2');
        $this->assertCount(0, $preferences->getChangeSet()->getCreate());
        $this->assertCount(1, $preferences->getChangeSet()->getUpdate());
        $preferences->commit();
    }
}