<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Web\Dashboard;

use Icinga\Exception\AlreadyExistsException;
use Icinga\Test\BaseDashboardTestCase;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;

class DashletTest extends BaseDashboardTestCase
{
    const TEST_DASHLET = 'Test Dashlet';

    protected function getTestDashlet(string $name = self::TEST_DASHLET): Dashlet
    {
        return new Dashlet($name, 'from/new-test');
    }

    public function testWhetherManageEntryManagesANewDashlet()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $pane = new Pane('Test Pane');
        $home->manageEntry($pane);

        $pane->manageEntry([$this->getTestDashlet(), $this->getTestDashlet('Test Me')]);

        $this->dashboard->load(self::TEST_HOME);

        $home = $this->dashboard->getActiveHome();
        $pane = $home->getActivePane();

        $this->assertCount(
            2,
            $pane->getEntries(),
            'Pane::manageEntry() could not manage a new Dashlet'
        );
    }

    /**
     * @depends testWhetherManageEntryManagesANewDashlet
     */
    public function testWhetherManageEntryManagesNewDashletsFromMultipleModules()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $pane = new Pane('Test Pane');
        $home->manageEntry($pane);

        $moduleDashlets = [
            'monitoring' => ['Service Problems' => $this->getTestDashlet('Service Problems')],
            'icingadb'   => ['Host Problems' => $this->getTestDashlet('Host Problems')]
        ];

        $pane->manageEntry($moduleDashlets);

        $this->dashboard->load(self::TEST_HOME);

        $home = $this->dashboard->getActiveHome();
        $pane = $home->getActivePane();

        $this->assertCount(
            2,
            $pane->getEntries(),
            'Pane::manageEntry() could not manage new Dashlets from multiple modules'
        );
    }

    /**
     * @depends testWhetherManageEntryManagesANewDashlet
     */
    public function testWhetherManageEntryUpdatesExistingDashlet()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $pane = new Pane('Test Pane');
        $home->manageEntry($pane);

        $pane->manageEntry($this->getTestDashlet());

        $this->dashboard->load(self::TEST_HOME);

        $home = $this->dashboard->getActiveHome();
        $pane = $home->getActivePane();
        $pane->getEntry(self::TEST_DASHLET)->setTitle('Hello');

        $pane->manageEntry($pane->getEntries());

        $this->dashboard->load(self::TEST_HOME);

        $home = $this->dashboard->getActiveHome();
        $pane = $home->getActivePane();

        $this->assertEquals(
            'Hello',
            $pane->getEntry(self::TEST_DASHLET)->getTitle(),
            'Pane::manageEntry() could not update existing Dashlet'
        );
    }

    /**
     * @depends testWhetherManageEntryUpdatesExistingDashlet
     */
    public function testWhetherManageEntryMovesADashletToAnotherPaneWithinTheSameHome()
    {
        $default = $this->getTestHome();
        $this->dashboard->manageEntry($default);

        $pane1 = new Pane('Test1');
        $pane2 = new Pane('Test2');

        $default->manageEntry([$pane1, $pane2]);

        $pane1->manageEntry($this->getTestDashlet());

        $this->dashboard->load(self::TEST_HOME);

        $default = $this->dashboard->getActiveHome();
        $pane1 = $default->getActivePane();
        $pane2 = $default->getEntry('Test2');

        // Move the dashlet from pane1 -> pane2
        $pane2->manageEntry($pane1->getEntries(), $pane1);

        $this->dashboard->load(self::TEST_HOME);

        $default = $this->dashboard->getActiveHome();
        $pane1 = $default->getActivePane();
        $pane2 = $default->getEntry('Test2');

        $this->assertCount(
            1,
            $pane2->getEntries(),
            'Pane::manageEntry() could not move a Dashlet to another Pane within the same Dashboard Home'
        );

        $this->assertCount(
            0,
            $pane1->getEntries(),
            'Pane::manageEntry() could not completely move a Dashlet to another Pane'
        );
    }

    /**
     * @depends testWhetherManageEntryMovesADashletToAnotherPaneWithinTheSameHome
     */
    public function testWhetherManageEntryMovesADashletToAnotherPaneAndAnotherHome()
    {
        $default = $this->getTestHome();
        $home = $this->getTestHome('Home Test');
        $this->dashboard->manageEntry([$default, $home]);

        $pane1 = new Pane('Test1');
        $pane2 = new Pane('Test2');

        $default->manageEntry($pane1);
        $home->manageEntry($pane2);

        $pane1->manageEntry($this->getTestDashlet());

        $this->dashboard->load(self::TEST_HOME, $pane1->getName(), true);

        $default = $this->dashboard->getActiveHome();
        $home = $this->dashboard->getEntry($home->getName());
        $home->loadDashboardEntries();

        $pane1 = $default->getActivePane();
        $pane2 = $home->getActivePane();

        // Move the dashlet from pane1 -> pane2
        $pane2->manageEntry($pane1->getEntries(), $pane1);

        $this->dashboard->load(self::TEST_HOME, $pane1->getName(), true);

        //$default = $this->dashboard->getActiveHome();
        $home = $this->dashboard->getEntry($home->getName());
        $home->loadDashboardEntries();

        $pane2 = $home->getActivePane();

        $this->assertCount(
            1,
            $pane2->getEntries(),
            'Pane::manageEntry() could not move a Dashlet to another Pane from another Dashboard Home'
        );
    }

    public function testWhetherManageEntryThrowsAnExceptionWhenDashboardHomeIsNotSet()
    {
        $this->expectException(\LogicException::class);

        $default = new Pane('Test Pane');
        $default->manageEntry($this->getTestDashlet());
    }

    public function testWhetherManageEntryThrowsAnExceptionOnDuplicatedError()
    {
        $this->expectException(AlreadyExistsException::class);

        $default = $this->getTestHome();
        $this->dashboard->manageEntry($default);

        // Dashboard Panes
        $pane1 = new Pane('Test1');
        $pane2 = new Pane('Test2');

        $default->manageEntry([$pane1, $pane2]);

        // Dashlets
        $pane1->manageEntry([$this->getTestDashlet(), $this->getTestDashlet('Test Me')]);
        $pane2->manageEntry([$this->getTestDashlet(), $this->getTestDashlet('Test Me')]);

        $this->dashboard->load($default->getName());

        $default = $this->dashboard->getActiveHome();
        $pane1 = $default->getActivePane();
        $pane2 = $default->getEntry('Test2');

        $pane1->manageEntry($this->getTestDashlet(), $pane2);
    }

    public function testWhetherManageEntryThrowsAnExceptionWhenPassingInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);

        $default = new Pane('Test Pane');
        $default->manageEntry($this->getTestDashlet(), $this->getTestHome());
    }

    public function testWhetherRemoveEntryRemovesExpectedDashletEntry()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $pane = new Pane('Test Pane');
        $home->manageEntry($pane);

        $pane->manageEntry($this->getTestDashlet());

        $this->dashboard->load(self::TEST_HOME, $pane->getName());

        $home = $this->dashboard->getActiveHome();
        $pane = $home->getActivePane();

        $pane->removeEntry(self::TEST_DASHLET);

        $this->dashboard->load();

        $home = $this->dashboard->getActiveHome();

        $this->assertFalse(
            $home->getActivePane()->hasEntry(self::TEST_DASHLET),
            'Pane::removeEntry() could not remove expected Dashlet'
        );
    }

    /**
     * @depends testWhetherRemoveEntryRemovesExpectedDashletEntry
     */
    public function testWhetherRemoveEntriesRemovesAllDashletEntries()
    {
        $home = $this->getTestHome();
        $this->dashboard->manageEntry($home);

        $pane = new Pane('Test Pane');
        $home->manageEntry($pane);

        $pane->manageEntry([$this->getTestDashlet(), $this->getTestDashlet('Test Me')]);

        $this->dashboard->load(self::TEST_HOME, $pane->getName());

        $home = $this->dashboard->getActiveHome();
        $pane = $home->getActivePane();

        $pane->removeEntries();

        $this->dashboard->load();

        $home = $this->dashboard->getActiveHome();

        $this->assertFalse(
            $home->getActivePane()->hasEntries(),
            'Pane::removeEntries() could not remove all Dashlet Entries'
        );
    }
}
