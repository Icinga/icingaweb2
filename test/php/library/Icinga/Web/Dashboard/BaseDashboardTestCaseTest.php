<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Web\Dashboard;

use Icinga\Exception\ProgrammingError;
use Icinga\Test\BaseDashboardTestCase;

class BaseDashboardTestCaseTest extends BaseDashboardTestCase
{
    public function testWhetherAddEntryAddsAnEntry()
    {
        $this->dashboard->addEntry($this->getTestHome());

        $this->assertTrue(
            $this->dashboard->hasEntry(self::TEST_HOME),
            'DashboardEntries::addEntry() could not add a Dashboard entry'
        );
    }

    /**
     * @depends testWhetherAddEntryAddsAnEntry
     */
    public function testWhetherAddEntryAddsDifferentEntries()
    {
        $this->dashboard->addEntry($this->getTestHome());
        $this->dashboard->addEntry($this->getTestHome('Second Home'));
        $this->dashboard->addEntry($this->getTestHome('Third Home'));

        $this->assertCount(
            3,
            $this->dashboard->getEntries(),
            'DashboardEntries::addEntry() could not add different Dashboard entries'
        );
    }

    /**
     * @depends testWhetherAddEntryAddsDifferentEntries
     */
    public function testMergeEntryWithSameEntryName()
    {
        $this->dashboard->addEntry($this->getTestHome());
        $this->dashboard->addEntry($this->getTestHome('Second Home'));
        $this->dashboard->addEntry($this->getTestHome('Second Home'));

        $this->assertCount(
            2,
            $this->dashboard->getEntries(),
            'DashboardEntries::addEntry() could not merge same Dashboard entries'
        );
    }

    /**
     * @depends testMergeEntryWithSameEntryName
     */
    public function testWhetherGetEntriesReturnsExpectedEntries()
    {
        $this->dashboard->addEntry($this->getTestHome());

        $this->assertCount(
            1,
            $this->dashboard->getEntries(),
            'DashboardEntries::getEntries() returns unexpected dashboard entries'
        );
    }

    public function testeWhetherGetEntryThrowsAnExceptionOnNotExistentEntryName()
    {
        $this->expectException(ProgrammingError::class);

        $this->dashboard->getEntry('test');
    }

    /**
     * @depends testeWhetherGetEntryThrowsAnExceptionOnNotExistentEntryName
     */
    public function testWhetherGetEntryGetsAnEntryByName()
    {
        $this->dashboard->addEntry($this->getTestHome());

        $this->assertEquals(
            self::TEST_HOME,
            $this->dashboard->getEntry(self::TEST_HOME)->getName(),
            'DashboardEntries:getEntry() could not return Dashboard entry by name'
        );
    }

    /**
     * @depends testMergeEntryWithSameEntryName
     */
    public function testWhetherHasEntriesHasNoEntries()
    {
        $this->assertFalse(
            $this->dashboard->hasEntries(),
            'DashboardEntries::hasEntries() has Dashboard entries but should not'
        );
    }

    /**
     * @depends testWhetherHasEntriesHasNoEntries
     */
    public function testWhetherHasEntriesHasEntries()
    {
        $this->dashboard->addEntry($this->getTestHome());

        $this->assertTrue(
            $this->dashboard->hasEntries(),
            'DashboardEntries::hasEntries() could not return valid expectation'
        );
    }

    /**
     * @depends testWhetherHasEntriesHasEntries
     */
    public function testWhetherGetEntryKeyTitleArrayReturnFormedArray()
    {
        $this->dashboard->addEntry(($this->getTestHome())->setTitle('First Home'));
        $this->dashboard->addEntry(($this->getTestHome('Test2')->setTitle('Second Home')));
        $this->dashboard->addEntry(($this->getTestHome('Test3')->setTitle('Third Home')));

        $expected = [
            self::TEST_HOME => 'First Home',
            'Test2'         => 'Second Home',
            'Test3'         => 'Third Home'
        ];

        $this->assertEquals(
            $expected,
            $this->dashboard->getEntryKeyTitleArr(),
            'DashboardEntries::getEntryKeyTitleArray() could not return valid expectation'
        );
    }
}
