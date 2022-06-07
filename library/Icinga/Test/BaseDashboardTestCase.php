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
    protected static $user;

    public function setUp(): void
    {
        parent::setUp();

        DBUtils::setConn($this->getDBConnection());
        $this->dashboard = new Dashboard();
        $this->dashboard->setUser($this->getUser());
    }

    protected function setupIcingaMock()
    {
        $moduleMock = Mockery::mock('Icinga\Application\Modules\Module');
        $searchUrl = (object) [
            'title' => 'Hosts',
            'url'   => 'icingadb/hosts?host.state.is_problem=y&view=minimal&limit=32&sort=host.state.severity desc'
        ];
        $moduleMock->shouldReceive('getSearchUrls')->andReturn([$searchUrl]);
        $moduleMock->shouldReceive('getName')->andReturn('test');
        $moduleMock->shouldReceive('getDashboard')->andReturn([]);
        $moduleMock->shouldReceive('getDashlets')->andReturn([]);

        $moduleManagerMock = Mockery::mock('Icinga\Application\Modules\Manager');
        $moduleManagerMock->shouldReceive('getLoadedModules')->andReturn([
            'test-module' => $moduleMock
        ]);

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
        if (self::$user === null) {
            $role = new Role();
            $role->setPermissions(['*']);

            self::$user = new User('test');
            self::$user->setRoles([$role]);
        }

        return self::$user;
    }

    protected function getTestHome(string $name = self::TEST_HOME)
    {
        return new DashboardHome($name);
    }
}
