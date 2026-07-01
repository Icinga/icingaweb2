<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Authentication\Role;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use Icinga\Web\Widget\SearchDashboard;

class SearchDashboardTest extends BaseTestCase
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
        $this->expectException(\Zend_Controller_Action_Exception::class);

        $user = new User('test');
        $user->setPermissions(['*' => '*']);
        $dashboard = new SearchDashboard();
        $dashboard->setUser($user);
        $dashboard = $dashboard->search('pending');
        $dashboard->getPane('search')->removeDashlets();
        $dashboard->render();
    }

    public function testWhetherSearchLoadsSearchDashletsFromModules()
    {
        $role = new Role();
        $role->setPermissions(['*']);

        $user = new User('test');
        $user->setRoles([$role]);

        $dashboard = new SearchDashboard();
        $dashboard->setUser($user);
        $dashboard = $dashboard->search('pending');

        $result = $dashboard->getPane('search')->hasDashlet('Hosts: pending');

        $this->assertTrue($result, 'Dashboard::search() could not load search dashlets from modules');
    }

    public function testWhetherSearchProvidesHintWhenSearchStringIsEmpty()
    {
        $role = new Role();
        $role->setPermissions(['*']);

        $user = new User('test');
        $user->setRoles([$role]);

        $dashboard = new SearchDashboard();
        $dashboard->setUser($user);
        $dashboard = $dashboard->search();

        $result = $dashboard->getPane('search')->hasDashlet('Ready to search');

        $this->assertTrue($result, 'Dashboard::search() could not get hint for search');
    }
}
