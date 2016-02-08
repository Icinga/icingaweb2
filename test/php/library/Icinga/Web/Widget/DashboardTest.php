<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Dashboard\Pane;
use Icinga\Web\Widget\Dashboard\Dashlet;

class DashletWithMockedView extends Dashlet
{
    public function view()
    {
        $mock = Mockery::mock('Icinga\Web\View');
        $mock->shouldReceive('escape');

        return $mock;
    }
}

class DashboardWithPredefinableActiveName extends Dashboard
{
    public $activeName = '';

    public function getTabs()
    {
        return Mockery::mock('Icinga\Web\Widget\Tabs')
            ->shouldReceive('getActiveName')->andReturn($this->activeName)
            ->shouldReceive('activate')
            ->getMock();
    }
}

class DashboardTest extends BaseTestCase
{
    public function tearDown()
    {
        parent::tearDown();
        Mockery::close(); // Necessary because some tests run in a separate process
    }

    public function testWhetherCreatePaneCreatesAPane()
    {
        $dashboard = new Dashboard();
        $pane = $dashboard->createPane('test')->getPane('test');

        $this->assertEquals('test', $pane->getTitle(), 'Dashboard::createPane() could not create a pane');
    }

    /**
     * @depends testWhetherCreatePaneCreatesAPane
     */
    public function testMergePanesWithDifferentPaneName()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $dashboard->createPane('test2');

        $panes = array(
            new Pane('test1a'),
            new Pane('test2a')
        );

        $dashboard->mergePanes($panes);

        $this->assertCount(4, $dashboard->getPanes(), 'Dashboard::mergePanes() could not merge different panes');
    }

    /**
     * @depends testWhetherCreatePaneCreatesAPane
     */
    public function testMergePanesWithSamePaneName()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $dashboard->createPane('test2');

        $panes = array(
            new Pane('test1'),
            new Pane('test3')
        );

        $dashboard->mergePanes($panes);

        $this->assertCount(3, $dashboard->getPanes(), 'Dashboard::mergePanes() could not merge same panes');
    }

    /**
     * @depends testWhetherCreatePaneCreatesAPane
     */
    public function testWhetherGetPaneReturnsAPaneByName()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');

        $pane = $dashboard->getPane('test1');

        $this->assertEquals(
            'test1',
            $pane->getName(),
            'Dashboard:getPane() could not return pane by name'
        );
    }

    /**
     * @expectedException \Icinga\Exception\ProgrammingError
     * @depends testWhetherCreatePaneCreatesAPane
     */
    public function testWhetherGetPaneThrowsAnExceptionOnNotExistentPaneName()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');

        $dashboard->getPane('test2');
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherRenderNotRendersPanesDisabledDashlet()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');
        $dashlet = new DashletWithMockedView('test', 'test', $pane);
        $dashlet->setDisabled(true);
        $pane->addDashlet($dashlet);

        $rendered = $dashboard->render();

        $greaterThanOne = strlen($rendered) > 1;

        $this->assertFalse(
            $greaterThanOne,
            'Dashboard::render() disabled dashlet is rendered, but should not'
        );
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherRenderRendersPanesEnabledDashlet()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');
        $dashlet = new DashletWithMockedView('test', 'test', $pane);
        $pane->addDashlet($dashlet);

        $rendered = $dashboard->render();

        $greaterThanOne = strlen($rendered) > 1;

        $this->assertTrue(
            $greaterThanOne,
            'Dashboard::render() could not render enabled dashlet'
        );
    }

    public function testWhetherRenderNotRendersNotExistentPane()
    {
        $dashboard = new Dashboard();

        $rendered = $dashboard->render();

        $greaterThanOne = strlen($rendered) > 1;

        $this->assertFalse(
            $greaterThanOne,
            'Dashboard::render() not existent pane ist rendered, but should not'
        );
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherGetPaneKeyTitleArrayReturnFormedArray()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1')->getPane('test1')->setTitle('Test1');
        $dashboard->createPane('test2')->getPane('test2')->setTitle('Test2');

        $result = $dashboard->getPaneKeyTitleArray();

        $expected = array(
            'test1' => 'Test1',
            'test2' => 'Test2'
        );

        $this->assertEquals(
            $expected,
            $result,
            'Dashboard::getPaneKeyTitleArray() could not return valid expectation'
        );
    }

    /**
     * @depends testWhetherCreatePaneCreatesAPane
     */
    public function testWhetherHasPanesHasPanes()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $dashboard->createPane('test2');

        $hasPanes = $dashboard->hasPanes();

        $this->assertTrue($hasPanes, 'Dashboard::hasPanes() could not return valid expectation');
    }

    public function testWhetherHasPanesHasNoPanes()
    {
        $dashboard = new Dashboard();

        $hasPanes = $dashboard->hasPanes();

        $this->assertFalse($hasPanes, 'Dashboard::hasPanes() has panes but should not');
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherRemoveDashletRemovesDashlet()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');

        $dashlet = new Dashlet('test', 'test', $pane);
        $pane->addDashlet($dashlet);

        $dashlet2 = new Dashlet('test2', 'test2', $pane);
        $pane->addDashlet($dashlet2);

        $dashboard->getPane('test1')->removeDashlet('test');
        $result = $dashboard->getPane('test1')->hasDashlet('test');

        $this->assertTrue(
            $result,
            'Dashboard::removeDashlet() could not remove dashlet from the pane'
        );
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherSetDashletUrlUpdatesTheDashletUrl()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');
        $dashlet = new Dashlet('test', 'test', $pane);
        $pane->addDashlet($dashlet);

        $dashboard->getPane('test1')->getDashlet('test')->setUrl('new');

        $this->assertEquals(
            'new',
            $dashlet->getUrl()->getPath(),
            'Dashboard::setDashletUrl() could not return valid expectation'
        );
    }

    /**
     * @expectedException \Icinga\Exception\ConfigurationError
     */
    public function testWhetherDetermineActivePaneThrowsAnExceptionIfCouldNotDetermine()
    {
        $dashboard = new Dashboard();
        $dashboard->determineActivePane();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException \Icinga\Exception\ProgrammingError
     * @depends testWhetherCreatePaneCreatesAPane
     */
    public function testWhetherDetermineActivePaneThrowsAnExceptionIfCouldNotDetermineInvalidPane()
    {
        $dashboard = new DashboardWithPredefinableActiveName();
        $dashboard->createPane('test1');

        Mockery::mock('alias:Icinga\Web\Url')
            ->shouldReceive('fromRequest->getParam')->andReturn('test2');

        $dashboard->determineActivePane();
    }

    /**
     * @depends testWhetherCreatePaneCreatesAPane
     */
    public function testWhetherDetermineActivePaneDeterminesActivePane()
    {
        $dashboard = new DashboardWithPredefinableActiveName();
        $dashboard->activeName = 'test2';
        $dashboard->createPane('test1');
        $dashboard->createPane('test2');

        $activePane = $dashboard->determineActivePane();

        $this->assertEquals(
            'test2',
            $activePane->getTitle(),
            'Dashboard::determineActivePane() could not determine active pane'
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @depends testWhetherCreatePaneCreatesAPane
     */
    public function testWhetherDetermineActivePaneDeterminesActiveValidPane()
    {
        $dashboard = new DashboardWithPredefinableActiveName();
        $dashboard->createPane('test1');
        $dashboard->createPane('test2');

        Mockery::mock('alias:Icinga\Web\Url')
            ->shouldReceive('fromRequest->getParam')->andReturn('test2');

        $activePane = $dashboard->determineActivePane();

        $this->assertEquals(
            'test2',
            $activePane->getTitle(),
            'Dashboard::determineActivePane() could not determine active pane'
        );
    }

    /**
     * @depends testWhetherCreatePaneCreatesAPane
     */
    public function testWhetherGetActivePaneReturnsActivePane()
    {
        $dashboard = new DashboardWithPredefinableActiveName();
        $dashboard->activeName = 'test2';
        $dashboard->createPane('test1');
        $dashboard->createPane('test2');

        $activePane = $dashboard->getActivePane();

        $this->assertEquals(
            'test2',
            $activePane->getTitle(),
            'Dashboard::determineActivePane() could not get expected active pane'
        );
    }

    public function testWhetherLoadConfigPanes()
    {
        $this->markTestIncomplete(
            'Dashboard::loadConfigPanes() is not fully implemented yet or rather not used'
        );
    }

    /**
     * @depends testWhetherLoadConfigPanes
     */
    public function testWhetherReadConfigPopulatesDashboard()
    {
        $this->markTestIncomplete(
            'Dashboard::readConfig() is not fully implemented yet or rather not used'
        );
    }
}
