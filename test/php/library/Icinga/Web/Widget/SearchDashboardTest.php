<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Test\BaseDashboardTestCase;
use Icinga\Web\Widget\SearchDashboard;

class SearchDashboardTest extends BaseDashboardTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Icinga::app()->getModuleManager()
            ->loadModule('test-module', '/tmp')
            ->getModule('test-module')
            ->provideSearchUrl('Hosts', 'monitoring/list/hosts?sort=host_severity&limit=10');
    }

    public function testWhetherRenderThrowsAnExceptionWhenHasNoDashlets()
    {
        $this->expectException(HttpNotFoundException::class);

        $dashboard = new SearchDashboard();
        $dashboard->setUser($this->getUser());
        $dashboard->search('pending');

        $searchHome = $dashboard->getActiveEntry();
        $searchHome->getEntry(SearchDashboard::SEARCH_PANE)->setEntries([]);
        $dashboard->render();
    }

    public function testWhetherSearchLoadsSearchDashletsFromModules()
    {
        $dashboard = new SearchDashboard();
        $dashboard->setUser($this->getUser());
        $dashboard->search('pending');

        $searchHome = $dashboard->getActiveEntry();
        $result = $searchHome->getEntry(SearchDashboard::SEARCH_PANE)->hasEntry('Hosts: pending');

        $this->assertTrue($result, 'SearchDashboard::search() could not load search dashlets from modules');
    }

    public function testWhetherSearchProvidesHintWhenSearchStringIsEmpty()
    {
        $dashboard = new SearchDashboard();
        $dashboard->setUser($this->getUser());
        $dashboard->search();

        $searchHome = $dashboard->getActiveEntry();
        $result = $searchHome->getEntry(SearchDashboard::SEARCH_PANE)->hasEntry('Ready to search');

        $this->assertTrue($result, 'SearchDashboard::search() could not get hint for search');
    }
}
