<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Web\Dashboard;

use Icinga\Exception\AlreadyExistsException;
use Icinga\Exception\ProgrammingError;
use Icinga\Test\BaseDashboardTestCase;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard\Pane;

class PaneTest extends BaseDashboardTestCase
{
    const TEST_PANE = 'Test Pane';

    protected function getTestPane(string $name = self::TEST_PANE): Pane
    {
        return new Pane($name);
    }

    public function testWhetherActivatePaneThrowsAnExceptionIfNotExists()
    {
        $this->expectException(ProgrammingError::class);

        $home = $this->getTestHome();
        $home->activatePane(new Pane(self::TEST_PANE));
    }

    public function testWhetherActivatePaneActivatesExpectedPane()
    {
        $home = $this->getTestHome();
        $home->addEntry($this->getTestPane());

        $home->activatePane($home->getEntry(self::TEST_PANE));

        $this->assertEquals(
            self::TEST_PANE,
            $home->getActivePane()->getName(),
            'DashboardHome::activatePane() could not activate expected Dashboard Pane'
        );
    }

    /**
     * @depends testWhetherActivatePaneActivatesExpectedPane
     */
    public function testWhetherLoadDashboardEntriesActivatesFirstPane()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $home->manageEntry($this->getTestPane());
        $home->manageEntry($this->getTestPane('Test Me'));

        $this->dashboard->load();
        $home = $this->dashboard->getActiveHome();

        $this->assertEquals(
            self::TEST_PANE,
            $home->getActivePane()->getName(),
            'DashboardHome::loadDashboardEntries() could not activate expected Dashboard Pane'
        );
    }

    /**
     * @depends testWhetherLoadDashboardEntriesActivatesFirstPane
     */
    public function testWhetherActivatePaneActivatesAPaneEntry()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $home->manageEntry(new Pane(self::TEST_PANE));

        $this->dashboard->load(self::TEST_HOME, self::TEST_PANE);
        $home = $this->dashboard->getActiveHome();

        $this->assertEquals(
            self::TEST_PANE,
            $home->getActivePane()->getName(),
            'DashboardHome::loadDashboardEntries() could not load and activate expected Dashboard Pane'
        );
    }

    /**
     * @depends testWhetherActivatePaneActivatesAPaneEntry
     */
    public function testWhetherGetActivePaneGetsExpectedPane()
    {
        $home = $this->getTestHome();
        $home->addEntry($this->getTestPane());
        $home->addEntry($this->getTestPane('Test Me'));

        $home->activatePane($home->getEntry('Test Me'));

        $this->assertEquals(
            'Test Me',
            $home->getActivePane()->getName(),
            'DashboardHome::getActivePane() could not determine valid active pane'
        );
    }

    /**
     * @depends testWhetherActivatePaneActivatesAPaneEntry
     */
    public function testWhetherManageEntryManagesANewPaneEntry()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $home->manageEntry($this->getTestPane());

        $this->dashboard->load(self::TEST_HOME);
        $home = $this->dashboard->getActiveHome();

        $this->assertCount(
            1,
            $home->getEntries(),
            'DashboardHome::manageEntry() could not manage a new Dashboard Pane'
        );
    }

    /**
     * @depends testWhetherManageEntryManagesANewPaneEntry
     */
    public function testWhetherManageEntryUpdatesExistingPaneEntry()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $home->manageEntry($this->getTestPane());

        $this->dashboard->load(self::TEST_HOME);

        $home = $this->dashboard->getActiveHome();
        $home->getActivePane()->setTitle('Hello');

        $home->manageEntry($home->getEntries());
        $this->dashboard->load(self::TEST_HOME);

        $home = $this->dashboard->getActiveHome();

        $this->assertEquals(
            'Hello',
            $home->getActivePane()->getTitle(),
            'DashboardHome::manageEntry() could not update existing Dashboard Pane'
        );
    }

    /**
     * @depends testWhetherManageEntryUpdatesExistingPaneEntry
     */
    public function testWhetherManageEntryMovesAPaneToAnotherExistingHome()
    {
        $home = $this->getTestHome('Second Home');
        $this->dashboard->manageEntry([$this->getTestHome(), $home]);

        $home->manageEntry([$this->getTestPane(), $this->getTestPane('Test Me')]);

        $this->dashboard->load('Second Home', null, true);

        $home = $this->dashboard->getActiveHome();
        /** @var DashboardHome $default */
        $default = $this->dashboard->getEntry(self::TEST_HOME);

        $default->manageEntry($home->getEntry(self::TEST_PANE), $home);
        $this->dashboard->load(self::TEST_HOME);

        $default = $this->dashboard->getActiveHome();

        $this->assertCount(
            1,
            $default->getEntries(),
            'DashboardHome::manageEntry() could not move a Dashboard Pane to another existing Dashboard Home'
        );
    }

    public function testWhetherManageEntryThrowsAnExceptionOnDuplicatedError()
    {
        $this->expectException(AlreadyExistsException::class);

        $default = $this->getTestHome();
        $home = $this->getTestHome('Second Home');

        // Dashboard Homes
        $this->dashboard->manageEntry([$home, $default]);

        // Dashboard Panes
        $default->manageEntry([$this->getTestPane(), $this->getTestPane('Test Me')]);
        $home->manageEntry([$this->getTestPane(), $this->getTestPane('Test Me')]);

        $this->dashboard->load();

        $home = $this->dashboard->getActiveHome();
        $default = $this->dashboard->getEntry(self::TEST_HOME);
        $default->loadDashboardEntries();

        $default->manageEntry($home->getEntry(self::TEST_PANE), $home);
    }

    public function testWhetherManageEntryThrowsAnExceptionWhenPassingInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);

        $default = $this->getTestHome();
        $default->manageEntry($this->getTestPane(), $this->getTestPane());
    }

    public function testWhetherRemoveEntryRemovesExpectedPaneEntry()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $home->manageEntry($this->getTestPane());

        $this->dashboard->load(self::TEST_HOME, self::TEST_PANE);

        $home = $this->dashboard->getActiveHome();

        $home->removeEntry(self::TEST_PANE);
        $this->dashboard->load();

        $home = $this->dashboard->getActiveHome();

        $this->assertFalse(
            $home->hasEntry(self::TEST_PANE),
            'DashboardHome::removeEntry() could not remove expected Dashboard Pane'
        );
    }

    /**
     * @depends testWhetherRemoveEntryRemovesExpectedPaneEntry
     */
    public function testWhetherRemoveEntriesRemovesAllDashboardPanes()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $home->manageEntry([$this->getTestPane(), $this->getTestPane('Test Me')]);

        $this->dashboard->load(self::TEST_HOME);

        $home = $this->dashboard->getActiveHome();

        $home->removeEntries();
        $this->dashboard->load();

        $home = $this->dashboard->getActiveHome();

        $this->assertFalse(
            $home->hasEntries(),
            'DashboardHome::removeEntries() could not remove all Dashboard Panes'
        );
    }
}
