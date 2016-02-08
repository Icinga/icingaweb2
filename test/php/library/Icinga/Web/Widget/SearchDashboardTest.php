<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web;

use Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use Icinga\Web\Widget\SearchDashboard;

class SearchDashboardTest extends BaseTestCase
{
    public function setUp()
    {
        $moduleMock = Mockery::mock('Icinga\Application\Modules\Module');
        $searchUrl = (object) array(
            'title' => 'Hosts',
            'url'   => 'monitoring/list/hosts?sort=host_severity&limit=10'
        );
        $moduleMock->shouldReceive('getSearchUrls')->andReturn(array(
            $searchUrl
        ));
        $moduleMock->shouldReceive('getName')->andReturn('test');

        $moduleManagerMock = Mockery::mock('Icinga\Application\Modules\Manager');
        $moduleManagerMock->shouldReceive('getLoadedModules')->andReturn(array(
            'test-module' => $moduleMock
        ));

        $bootstrapMock = $this->setupIcingaMock();
        $bootstrapMock->shouldReceive('getModuleManager')->andReturn($moduleManagerMock);
    }

    /**
     * @expectedException Zend_Controller_Action_Exception
     */
    public function testWhetherRenderThrowsAnExceptionWhenHasNoDashlets()
    {
        $user = new User('test');
        $user->setPermissions(array('*' => '*'));
        $dashboard = new SearchDashboard();
        $dashboard->setUser($user);
        $dashboard = $dashboard->search('pending');
        $dashboard->getPane('search')->removeDashlets();
        $dashboard->render();
    }

    public function testWhetherSearchLoadsSearchDashletsFromModules()
    {
        $user = new User('test');
        $user->setPermissions(array('*' => '*'));
        $dashboard = new SearchDashboard();
        $dashboard->setUser($user);
        $dashboard = $dashboard->search('pending');

        $result = $dashboard->getPane('search')->hasDashlet('Hosts: pending');

        $this->assertTrue($result, 'Dashboard::search() could not load search dashlets from modules');
    }

    public function testWhetherSearchProvidesHintWhenSearchStringIsEmpty()
    {
        $user = new User('test');
        $user->setPermissions(array('*' => '*'));
        $dashboard = new SearchDashboard();
        $dashboard->setUser($user);
        $dashboard = $dashboard->search();

        $result = $dashboard->getPane('search')->hasDashlet('Ready to search');

        $this->assertTrue($result, 'Dashboard::search() could not get hint for search');
    }
}
