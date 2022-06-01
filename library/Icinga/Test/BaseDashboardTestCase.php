<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Test;

use Icinga\Authentication\Role;
use Icinga\User;
use Icinga\Util\DBUtils;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use ipl\Sql\Connection;
use Mockery;
use PDO;

class BaseDashboardTestCase extends BaseTestCase
{
    const TEST_HOME = 'Test Home';

    /** @var Dashboard */
    protected $dashboard;

    /** @var User A test user for the dashboards */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        DBUtils::setConn($this->getDBConnection());
        $this->dashboard = new Dashboard();
        $this->dashboard->setUser($this->getUser());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close(); // Necessary because some tests run in a separate process
    }

    protected function setupIcingaMock()
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
        $moduleMock->shouldReceive('getDashboard')->andReturn([]);
        $moduleMock->shouldReceive('getDashlets')->andReturn([]);

        $moduleManagerMock = Mockery::mock('Icinga\Application\Modules\Manager');
        $moduleManagerMock->shouldReceive('getLoadedModules')->andReturn(array(
            'test-module' => $moduleMock
        ));

        $bootstrapMock = parent::setupIcingaMock();
        $bootstrapMock->shouldReceive('getModuleManager')->andReturn($moduleManagerMock);

        return $bootstrapMock;
    }

    protected function getDBConnection(): Connection
    {
        $conn = new Connection([
            'db'      => 'sqlite',
            'dbname'  => ':memory:',
            'options' => [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ]
        ]);

        $fixtures = @file_get_contents(__DIR__ . '/fixtures.sql');
        $conn->exec($fixtures);

        return $conn;
    }

    protected function getUser(): User
    {
        if ($this->user === null) {
            $role = new Role();
            $role->setPermissions(['*']);

            $this->user = new User('test');
            $this->user->setRoles([$role]);
        }

        return $this->user;
    }

    protected function getTestHome(string $name = self::TEST_HOME)
    {
        return new DashboardHome($name);
    }
}