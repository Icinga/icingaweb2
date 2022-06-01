<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Web\Dashboard;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Icinga\Exception\ProgrammingError;
use Icinga\Test\BaseDashboardTestCase;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Widget\Tab;
use Mockery;

class DashboardWithPredefinableActiveName extends Dashboard
{
    public $activeName = '';

    public function getTabs()
    {
        $activeTab = $this->activeName ? new Tab(['name' => $this->activeName]) : null;

        return Mockery::mock('ipl\Web\Widget\Tabs')
            ->shouldReceive('getActiveTab')->andReturn($activeTab)
            ->shouldReceive('activate')
            ->getMock();
    }
}

class PaneTest extends BaseDashboardTestCase
{
    public function testWhetherDetermineActivePaneThrowsAnExceptionIfCouldNotDetermine()
    {
        $this->expectException(\Icinga\Exception\ConfigurationError::class);

        $home = $this->getTestHome();
        $home->determineActivePane($this->dashboard->getTabs());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState
     */
    public function testWhetherDetermineActivePaneThrowsAnExceptionIfCouldNotDetermineInvalidPane()
    {
        $this->expectException(ProgrammingError::class);

        Mockery::mock('alias:ipl\Web\Url')->shouldReceive('fromRequest->getParam')->andReturn('test');

        $dashboard = new DashboardWithPredefinableActiveName();
        $home = $this->getTestHome();

        $home->determineActivePane($dashboard->getTabs());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWhetherDetermineActivePaneDeterminesActiveValidPane()
    {
        Mockery::mock('alias:ipl\Web\Url')->shouldReceive('fromRequest->getParam')->andReturn('test2');

        $home = $this->getTestHome();
        $home->addEntry(new Pane('test1'));
        $home->addEntry(new Pane('test2'));

        $dashboard = new DashboardWithPredefinableActiveName();
        $activePane = $home->determineActivePane($dashboard->getTabs());

        $this->assertEquals(
            'test2',
            $activePane->getName(),
            'DashboardHome::determineActivePane() could not determine valid active pane'
        );
    }

    public function testWhetherDetermineActivePaneActivatesTheFirstPane()
    {
        $home = $this->getTestHome();
        $home->addEntry(new Pane('test1'));
        $home->addEntry(new Pane('test2'));

        $this->dashboard->addEntry($home)->activateHome($home);

        $activePane = $home->determineActivePane($this->dashboard->getTabs());
        $this->assertEquals(
            'test1',
            $activePane->getName(),
            'DashboardHome::determineActivePane() could not determine/activate the first pane'
        );
    }

    public function testWhetherDetermineActivePaneDeterminesActivePane()
    {
        $dashboard = new DashboardWithPredefinableActiveName();
        $dashboard->activeName = 'test2';

        $home = $this->getTestHome();
        $home->addEntry(new Pane('test1'));
        $home->addEntry(new Pane('test2'));

        $activePane = $home->determineActivePane($dashboard->getTabs());
        $this->assertEquals(
            'test2',
            $activePane->getName(),
            'DashboardHome::determineActivePane() could not determine active pane'
        );
    }
}
