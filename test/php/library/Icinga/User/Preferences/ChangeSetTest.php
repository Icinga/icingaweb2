<?php
namespace Tests\Icinga\User\Preferences;

require_once __DIR__. '/../../../../../../library/Icinga/User/Preferences/ChangeSet.php';

use \PHPUnit_Framework_TestCase;
use Icinga\User\Preferences\ChangeSet;

class ChangeSetTest extends PHPUnit_Framework_TestCase
{
    public function testAppendCreate()
    {
        $changeSet = new ChangeSet();
        $changeSet->appendCreate('test.key1', 'ok1');
        $changeSet->appendCreate('test.key2', 'ok2');

        $creates = $changeSet->getCreate();

        $this->assertCount(2, $creates);
        $this->assertTrue($changeSet->hasChanges());

        $this->assertEquals(
            array(
                'test.key1' => 'ok1',
                'test.key2' => 'ok2'
            ),
            $creates
        );
    }

    public function testAppendUpdate()
    {
        $changeSet = new ChangeSet();
        $changeSet->appendUpdate('test.key3', 'ok1');
        $changeSet->appendUpdate('test.key4', 'ok2');
        $changeSet->appendUpdate('test.key5', 'ok3');

        $updates = $changeSet->getUpdate();

        $this->assertCount(3, $updates);
        $this->assertTrue($changeSet->hasChanges());

        $this->assertEquals(
            array(
                'test.key3' => 'ok1',
                'test.key4' => 'ok2',
                'test.key5' => 'ok3'
            ),
            $updates
        );
    }

    public function testAppendDelete()
    {
        $changeSet = new ChangeSet();
        $changeSet->appendDelete('test.key6');
        $changeSet->appendDelete('test.key7');
        $changeSet->appendDelete('test.key8');
        $changeSet->appendDelete('test.key9');

        $deletes = $changeSet->getDelete();

        $this->assertCount(4, $deletes);
        $this->assertTrue($changeSet->hasChanges());

        $this->assertEquals(
            array(
                'test.key6',
                'test.key7',
                'test.key8',
                'test.key9',
            ),
            $deletes
        );
    }

    public function testObjectReset()
    {
        $changeSet = new ChangeSet();
        $changeSet->appendCreate('test.key1', 'ok');
        $changeSet->appendCreate('test.key2', 'ok');
        $changeSet->appendUpdate('test.key3', 'ok');
        $changeSet->appendUpdate('test.key4', 'ok');
        $changeSet->appendUpdate('test.key5', 'ok');
        $changeSet->appendDelete('test.key6');
        $changeSet->appendDelete('test.key7');
        $changeSet->appendDelete('test.key8');

        $this->assertTrue($changeSet->hasChanges());
        $changeSet->clear();
        $this->assertFalse($changeSet->hasChanges());
    }
}