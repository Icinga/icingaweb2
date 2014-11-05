<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web;

// Necessary as some of these tests disable phpunit's preservation
// of the global state (e.g. autoloaders are in the global state)
require_once realpath(dirname(__FILE__) . '/../../../../bootstrap.php');

use Mockery;
use Icinga\Application\Icinga;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Dashboard\Pane;
use Icinga\Web\Widget\Dashboard\Component;
use Icinga\Test\BaseTestCase;

class ComponentWithMockedView extends Component
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

    public function setUp()
    {
        $moduleMock = Mockery::mock('Icinga\Application\Modules\Module');
        $moduleMock->shouldReceive('getPaneItems')->andReturn(array(
            'test-pane' => new Pane('Test Pane')
        ));

        $moduleManagerMock = Mockery::mock('Icinga\Application\Modules\Manager');
        $moduleManagerMock->shouldReceive('getLoadedModules')->andReturn(array(
            'test-module' => $moduleMock
        ));

        $bootstrapMock = $this->setupIcingaMock();
        $bootstrapMock->shouldReceive('getModuleManager')->andReturn($moduleManagerMock);
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
     * @depends testWhetherCreatePaneCreatesAPane
     */
    public function testLoadPaneItemsProvidedByEnabledModules()
    {
        $dashboard = Dashboard::load();

        $this->assertCount(
            1,
            $dashboard->getPanes(),
            'Dashboard::load() could not load panes from enabled modules'
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
    public function testWhetherRenderNotRendersPanesDisabledComponent()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');
        $component = new ComponentWithMockedView('test', 'test', $pane);
        $component->setDisabled(true);
        $pane->addComponent($component);

        $rendered = $dashboard->render();

        $greaterThanOne = strlen($rendered) > 1;

        $this->assertFalse(
            $greaterThanOne,
            'Dashboard::render() disabled component is rendered, but should not'
        );
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherRenderRendersPanesEnabledComponent()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');
        $component = new ComponentWithMockedView('test', 'test', $pane);
        $pane->addComponent($component);

        $rendered = $dashboard->render();

        $greaterThanOne = strlen($rendered) > 1;

        $this->assertTrue(
            $greaterThanOne,
            'Dashboard::render() could not render enabled component'
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
    public function testWhetherRemoveComponentRemovesComponent()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');

        $component = new Component('test', 'test', $pane);
        $pane->addComponent($component);

        $component2 = new Component('test2', 'test2', $pane);
        $pane->addComponent($component2);

        $dashboard->removeComponent('test1', 'test');

        $result = $dashboard->getPane('test1')->hasComponent('test');

        $this->assertFalse(
            $result,
            'Dashboard::removeComponent() could not remove component from the pane'
        );
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherRemoveComponentRemovesComponentByConcatenation()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');

        $component = new Component('test', 'test', $pane);
        $pane->addComponent($component);

        $component2 = new Component('test2', 'test2', $pane);
        $pane->addComponent($component2);

        $dashboard->removeComponent('test1.test', null);

        $result = $dashboard->getPane('test1')->hasComponent('test');

        $this->assertFalse(
            $result,
            'Dashboard::removeComponent() could not remove component from the pane'
        );
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherToArrayReturnsDashboardStructureAsArray()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');

        $component = new Component('test', 'test', $pane);
        $pane->addComponent($component);

        $result = $dashboard->toArray();

        $expected = array(
            'test1' => array(
                'title' => 'test1'
            ),
            'test1.test' => array(
                'url' => 'test'
            )
        );

        $this->assertEquals(
            $expected,
            $result,
            'Dashboard::toArray() could not return valid expectation'
        );
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherSetComponentUrlUpdatesTheComponentUrl()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');
        $component = new Component('test', 'test', $pane);
        $pane->addComponent($component);

        $dashboard->setComponentUrl('test1', 'test', 'new');

        $this->assertEquals(
            'new',
            $component->getUrl()->getPath(),
            'Dashboard::setComponentUrl() could not return valid expectation'
        );
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherSetComponentUrlUpdatesTheComponentUrlConcatenation()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');
        $component = new Component('test', 'test', $pane);
        $pane->addComponent($component);

        $dashboard->setComponentUrl('test1.test', null, 'new');

        $this->assertEquals(
            'new',
            $component->getUrl()->getPath(),
            'Dashboard::setComponentUrl() could not return valid expectation'
        );
    }

    /**
     * @depends testWhetherGetPaneReturnsAPaneByName
     */
    public function testWhetherSetComponentUrlUpdatesTheComponentUrlNotExistentPane()
    {
        $dashboard = new Dashboard();
        $dashboard->createPane('test1');
        $pane = $dashboard->getPane('test1');
        $component = new Component('test', 'test', $pane);
        $pane->addComponent($component);

        $dashboard->setComponentUrl('test3.test', null, 'new');

        $result = $dashboard->getPane('test3')->getComponent('test');

        $this->assertEquals(
            'new',
            $result->getUrl()->getPath(),
            'Dashboard::setComponentUrl() could not return valid expectation'
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
