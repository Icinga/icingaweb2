<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web;

use Mockery;
use Icinga\Application\Icinga;
use Icinga\Web\Widget\SearchDashboard;
use Icinga\Test\BaseTestCase;

class SearchDashboardTest extends BaseTestCase
{
    public function tearDown()
    {
        parent::tearDown();
        Mockery::close(); // Necessary because some tests run in a separate process
    }

    protected function setupIcingaMock(\Zend_Controller_Request_Abstract $request)
    {
        $moduleMock = Mockery::mock('Icinga\Application\Modules\Module');
        $searchUrl = (object) array(
            'title' => 'Hosts',
            'url'   => 'monitoring/list/hosts?sort=host_severity&limit=10'
        );
        $moduleMock->shouldReceive('getSearchUrls')->andReturn(array(
            $searchUrl
        ));

        $moduleManagerMock = Mockery::mock('Icinga\Application\Modules\Manager');
        $moduleManagerMock->shouldReceive('getLoadedModules')->andReturn(array(
            'test-module' => $moduleMock
        ));

        $bootstrapMock = Mockery::mock('Icinga\Application\ApplicationBootstrap')->shouldDeferMissing();
        $bootstrapMock->shouldReceive('getFrontController->getRequest')->andReturnUsing(
            function () use ($request) { return $request; }
        )->shouldReceive('getApplicationDir')->andReturn(self::$appDir);

        $bootstrapMock->shouldReceive('getModuleManager')->andReturn($moduleManagerMock);

        Icinga::setApp($bootstrapMock, true);
    }

    /**
     * @expectedException Zend_Controller_Action_Exception
     */
    public function testFoo()
    {
        $dashboard = SearchDashboard::load('pending');
        $dashboard->getPane('search')->removeComponents();
        $dashboard->render();
    }

    public function testWhetherLoadLoadsSearchDashletsFromModules()
    {
        $dashboard = SearchDashboard::load('pending');

        $result = $dashboard->getPane('search')->hasComponent('Hosts: pending');

        $this->assertTrue($result, 'Dashboard::load() could not load search dashlets from modules');
    }


    public function testWhetherLoadProvidesHint()
    {
        $dashboard = SearchDashboard::load('');

        $result = $dashboard->getPane('search')->hasComponent('Ready to search');

        $this->assertTrue($result, 'Dashboard::load() could not get hint for search');
    }
}
