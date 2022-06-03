<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Web\Dashboard;

use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Exception\ProgrammingError;
use Icinga\Test\BaseDashboardTestCase;

class HomeTest extends BaseDashboardTestCase
{
    public function testWhetherManageEntryManagesANewHomeEntry()
    {
        $this->dashboard->manageEntry($this->getTestHome());
        $this->dashboard->load(self::TEST_HOME);

        $this->assertCount(
            1,
            $this->dashboard->getEntries(),
            'Dashboard::manageEntry() could not manage a new Dashboard Home'
        );
    }

    /**
     * @depends testWhetherManageEntryManagesANewHomeEntry
     */
    public function testWhetherManageEntryUpdatesExistingHomeEntry()
    {
        $this->dashboard->manageEntry($this->getTestHome());
        $this->dashboard->load(self::TEST_HOME);

        $home = $this->dashboard->getActiveHome();
        $home->setTitle('Hello');

        $this->dashboard->manageEntry($home);
        $this->dashboard->load(self::TEST_HOME);

        $home = $this->dashboard->getActiveHome();

        $this->assertEquals(
            'Hello',
            $home->getTitle(),
            'Dashboard::manageEntry() could not update existing Dashboard Home'
        );
    }

    /**
     * @depends testWhetherManageEntryUpdatesExistingHomeEntry
     */
    public function testWhetherRemoveEntryThrowsAnExceptionIfNotExists()
    {
        $this->expectException(ProgrammingError::class);

        $this->dashboard->removeEntry('test');
    }

    /**
     * @depends testWhetherRemoveEntryThrowsAnExceptionIfNotExists
     */
    public function testWhetherRemoveEntryRemovesExpectedHomeEntry()
    {
        $this->dashboard->manageEntry($this->getTestHome('Second Home'));
        $this->dashboard->load();

        $this->dashboard->removeEntry('Second Home');
        $this->dashboard->load();

        $this->assertFalse(
            $this->dashboard->hasEntry('Second Home'),
            'Dashboard::removeEntry() could not remove expected Dashboard Home entry'
        );
    }

    /**
     * @depends testWhetherRemoveEntryRemovesExpectedHomeEntry
     */
    public function testWhetherRemoveEntriesRemovesAllHomeEntries()
    {
        $this->dashboard->manageEntry($this->getTestHome('Second Home'));
        $this->dashboard->load();

        $this->dashboard->removeEntries();
        $this->dashboard->load();

        $this->assertFalse(
            $this->dashboard->hasEntries(),
            'Dashboard::removeEntries() could not remove all Dashboard Homes'
        );
    }

    /**
     * @depends testWhetherRemoveEntriesRemovesAllHomeEntries
     */
    public function testWhetherLoadHomesLoadsNullHomes()
    {
        $this->dashboard->load();

        $this->assertFalse(
            $this->dashboard->hasEntries(),
            'Dashboard::load() has loaded Dashboard Homes but should not'
        );
    }

    public function testWhetherLoadHomeByNameThrowsAnExceptionIfNotExists()
    {
        $this->expectException(HttpNotFoundException::class);

        $this->dashboard->load('test');
    }

    /**
     * @depends testWhetherActivateHomeActivatesAHomeEntry
     */
    public function testWhetherLoadHomesByNameAndLoadAllParamSetLoadsAllHomesAndActivatesTheExpectedHome()
    {
        $this->dashboard->manageEntry([$this->getTestHome(), $this->getTestHome('Second Home')]);
        $this->dashboard->load('Second Home', null, true);

        $this->assertCount(
            2,
            $this->dashboard->getEntries(),
            'Dashboard::load() could not all expected Dashboard Homes'
        );

        $this->assertEquals(
            'Second Home',
            $this->dashboard->getActiveHome()->getName(),
            'Dashboard::load() could not load all expected Dashboard Homes and activate expected Dashboard Home'
        );
    }

    public function testWhetherActivateHomeThrowsAnExceptionIfNotExists()
    {
        $this->expectException(ProgrammingError::class);

        $this->dashboard->activateHome($this->getTestHome('Activate Home'));
    }

    public function testWhetherLoadHomesActivatesFirstHome()
    {
        $this->dashboard->manageEntry([$this->getTestHome(), $this->getTestHome('Second Home')]);

        $this->dashboard->load();

        $this->assertEquals(
            self::TEST_HOME,
            $this->dashboard->getActiveHome()->getName(),
            'Dashboard::load() could not activate expected Dashboard Home'
        );
    }

    /**
     * @depends testWhetherLoadHomesActivatesFirstHome
     */
    public function testWhetherActivateHomeActivatesAHomeEntry()
    {
        $this->dashboard->manageEntry([$this->getTestHome(), $this->getTestHome('Second Home')]);
        $this->dashboard->load();

        $active = $this->dashboard->getEntry('Second Home');
        $this->dashboard->activateHome($active);

        $this->assertTrue($active->isActive(), 'Dashboard::activateHome() could not activate expected Dashboard Home');
    }

    /**
     * @depends testWhetherActivateHomeActivatesAHomeEntry
     */
    public function testWhetherGetActiveHomeGetsExpectedHome()
    {
        $this->dashboard->addEntry($this->getTestHome());
        $this->dashboard->addEntry($this->getTestHome('Second Home'));

        $active = $this->dashboard->getEntry(self::TEST_HOME);
        $this->dashboard->activateHome($active);

        $this->assertEquals(
            self::TEST_HOME,
            $this->dashboard->getActiveHome()->getName(),
            'Dashboard::getActiveHome() could not return expected Dashboard Home'
        );
    }
}
