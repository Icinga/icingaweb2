<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Module\Migrate\Clicommands;

use Icinga\Application\Cli;
use Icinga\Application\Config;
use Icinga\Application\Modules\Manager;
use Icinga\Cli\Params;
use Icinga\Data\ConfigObject;
use Icinga\Exception\IcingaException;
use Icinga\Exception\MissingParameterException;
use Icinga\File\Storage\TemporaryLocalFileStorage;
use Icinga\Module\Migrate\Clicommands\ToicingadbCommand;
use PHPUnit\Framework\TestCase;

class ToIcingadbCommandTest extends TestCase
{
    protected $config = [
        'dashboards' => [
            'initial' => [
                'hosts' => [
                    'title' => 'Hosts'
                ],
                'hosts.problems' => [
                    'title' => 'Host Problems',
                    'url'   => 'monitoring/list/hosts?host_problem=1'
                ],
                'hosts.group_members' => [
                    'title' => 'Group Members',
                    'url'   => 'monitoring/list/hosts?hostgroup_name=group1|hostgroup_name=group2'
                ],
                'hosts.variables' => [
                    'title' => 'Host Variables',
                    'url'   => 'monitoring/list/hosts?(_host_foo=bar&_host_bar=foo)|_host_rab=oof'
                ],
                'hosts.wildcards' => [
                    'title' => 'Host Wildcards',
                    'url'   => 'monitoring/list/hosts?host_name=*foo*|host_name=*bar*'
                ],
                'icingadb' => [
                    'title' => 'Icinga DB'
                ],
                'icingadb.no-wildcards' => [
                    'title' => 'No Wildcards',
                    'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name=linux-hosts'
                ],
                'icingadb.wildcards' => [
                    'title' => 'Wildcards',
                    'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name=*linux*'
                ],
                'icingadb.also-wildcards' => [
                    'title' => 'Also Wildcards',
                    'url'   => 'icingadb/hosts?host.name=*foo*'
                ],
                'not-monitoring-or-icingadb' => [
                    'title' => 'Not Monitoring Or Icinga DB'
                ],
                'not-monitoring-or-icingadb.something' => [
                    'title' => 'Something',
                    'url'   => 'somewhere/something?foo=*bar*'
                ]
            ],
            'expected' => [
                'hosts' => [
                    'title' => 'Hosts'
                ],
                'hosts.problems' => [
                    'title' => 'Host Problems',
                    'url'   => 'icingadb/hosts?host.state.is_problem=y'
                ],
                'hosts.group_members' => [
                    'title' => 'Group Members',
                    'url'   => 'icingadb/hosts?hostgroup.name=group1|hostgroup.name=group2'
                ],
                'hosts.variables' => [
                    'title' => 'Host Variables',
                    'url'   => 'icingadb/hosts?(host.vars.foo=bar&host.vars.bar=foo)|host.vars.rab=oof'
                ],
                'hosts.wildcards' => [
                    'title' => 'Host Wildcards',
                    'url'   => 'icingadb/hosts?host.name~*foo*|host.name~*bar*'
                ],
                'icingadb' => [
                    'title' => 'Icinga DB'
                ],
                'icingadb.no-wildcards' => [
                    'title' => 'No Wildcards',
                    'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name=linux-hosts'
                ],
                'icingadb.wildcards' => [
                    'title' => 'Wildcards',
                    'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name~*linux*'
                ],
                'icingadb.also-wildcards' => [
                    'title' => 'Also Wildcards',
                    'url'   => 'icingadb/hosts?host.name~*foo*'
                ],
                'not-monitoring-or-icingadb' => [
                    'title' => 'Not Monitoring Or Icinga DB'
                ],
                'not-monitoring-or-icingadb.something' => [
                    'title' => 'Something',
                    'url'   => 'somewhere/something?foo=*bar*'
                ]
            ]
        ],
        'host-actions' => [
            'initial' => [
                'hosts' => [
                    'type'      => 'host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host_name=*foo*'
                ]
            ],
            'expected' => [
                'hosts' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host.name~*foo*'
                ]
            ]
        ],
        'icingadb-host-actions' => [
            'initial' => [
                'hosts' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host.name=*foo*'
                ]
            ],
            'expected' => [
                'hosts' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host.name~*foo*'
                ]
            ]
        ],
        'service-actions' => [
            'initial' => [
                'services' => [
                    'type'      => 'service-action',
                    'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                    'filter'    => '_service_foo=bar&_service_bar=*foo*'
                ]
            ],
            'expected' => [
                'services' => [
                    'type'      => 'icingadb-service-action',
                    'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                    'filter'    => 'service.vars.foo=bar&service.vars.bar~*foo*'
                ]
            ]
        ],
        'icingadb-service-actions' => [
            'initial' => [
                'services' => [
                    'type'      => 'icingadb-service-action',
                    'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                    'filter'    => 'service.vars.foo=*bar*'
                ]
            ],
            'expected' => [
                'services' => [
                    'type'      => 'icingadb-service-action',
                    'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                    'filter'    => 'service.vars.foo~*bar*'
                ]
            ]
        ],
        'shared-host-actions' => [
            'initial' => [
                'shared-hosts' => [
                    'type'      => 'host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host_name=*foo*',
                    'owner'     => 'test'
                ],
                'other-hosts' => [
                    'type'      => 'host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host_name=*foo*',
                    'owner'     => 'not-test'
                ]
            ],
            'expected' => [
                'shared-hosts' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host.name~*foo*',
                    'owner'     => 'test'
                ]
            ]
        ],
        'host-actions-legacy-macros' => [
            'initial' => [
                'hosts' => [
                    'type'      => 'host-action',
                    'url'       => 'example.com/search?q=$HOSTNAME$,$HOSTADDRESS$,$HOSTADDRESS6$',
                    'filter'    => 'host_name=*foo*'
                ]
            ],
            'expected' => [
                'hosts' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'example.com/search?q=$host.name$,$host.address$,$host.address6$',
                    'filter'    => 'host.name~*foo*'
                ]
            ]
        ],
        'service-actions-legacy-macros' => [
            'initial' => [
                'services' => [
                    'type'      => 'service-action',
                    'url'       => 'example.com/search?q=$SERVICEDESC$,$HOSTNAME$,$HOSTADDRESS$,$HOSTADDRESS6$',
                    'filter'    => '_service_foo=bar&_service_bar=*foo*'
                ]
            ],
            'expected' => [
                'services' => [
                    'type'      => 'icingadb-service-action',
                    'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                    'filter'    => 'service.vars.foo=bar&service.vars.bar~*foo*'
                ]
            ]
        ],
        'all-roles' => [
            'initial' => [
                'no-wildcards' => [
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
                ],
                'wildcards' => [
                    'monitoring/filter/objects' => 'host_name=*foo*|hostgroup_name=*foo*'
                ],
                'blacklist' => [
                    'monitoring/blacklist/properties'   => 'host.vars.foo,service.vars.bar*,host.vars.a.**.d'
                ],
                'full-access' => [
                    'permissions'   => 'module/monitoring,monitoring/*'
                ],
                'general-read-access' => [
                    'permissions'   => 'module/monitoring'
                ],
                'general-write-access' => [
                    'permissions'   => 'module/monitoring,monitoring/command/*'
                ],
                'full-fine-grained-access' => [
                    'permissions'   => 'module/monitoring'
                        . ',monitoring/command/schedule-check'
                        . ',monitoring/command/acknowledge-problem'
                        . ',monitoring/command/remove-acknowledgement'
                        . ',monitoring/command/comment/add'
                        . ',monitoring/command/comment/delete'
                        . ',monitoring/command/downtime/schedule'
                        . ',monitoring/command/downtime/delete'
                        . ',monitoring/command/process-check-result'
                        . ',monitoring/command/feature/instance'
                        . ',monitoring/command/feature/object/active-checks'
                        . ',monitoring/command/feature/object/passive-checks'
                        . ',monitoring/command/feature/object/notifications'
                        . ',monitoring/command/feature/object/event-handler'
                        . ',monitoring/command/feature/object/flap-detection'
                        . ',monitoring/command/send-custom-notification'
                ],
                'full-with-refusals' => [
                    'permissions'   => 'module/monitoring,monitoring/command/*',
                    'refusals'      => 'monitoring/command/downtime/*,monitoring/command/feature/instance'
                ],
                'active-only' => [
                    'permissions'   => 'module/monitoring,monitoring/command/schedule-check/active-only'
                ],
                'no-monitoring-contacts' => [
                    'permissions'   => 'module/monitoring,no-monitoring/contacts'
                ],
                'reporting-only' => [
                    'permissions'   => 'module/reporting'
                ]
            ],
            'expected' => [
                'no-wildcards' => [
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo',
                    'icingadb/filter/objects'   => 'host.name=foo|hostgroup.name=foo'
                ],
                'wildcards' => [
                    'monitoring/filter/objects' => 'host_name=*foo*|hostgroup_name=*foo*',
                    'icingadb/filter/objects'   => 'host.name~*foo*|hostgroup.name~*foo*'
                ],
                'blacklist' => [
                    'monitoring/blacklist/properties'   => 'host.vars.foo,service.vars.bar*,host.vars.a.**.d',
                    'icingadb/denylist/variables'       => 'foo,bar*,a.*.d'
                ],
                'full-access' => [
                    'permissions'   => 'module/monitoring,monitoring/*'
                ],
                'general-read-access' => [
                    'permissions'   => 'module/monitoring'
                ],
                'general-write-access' => [
                    'permissions'   => 'module/monitoring,monitoring/command/*,icingadb/command/*'
                ],
                'full-fine-grained-access' => [
                    'permissions'   => 'module/monitoring'
                        . ',monitoring/command/schedule-check'
                        . ',icingadb/command/schedule-check'
                        . ',monitoring/command/acknowledge-problem'
                        . ',icingadb/command/acknowledge-problem'
                        . ',monitoring/command/remove-acknowledgement'
                        . ',icingadb/command/remove-acknowledgement'
                        . ',monitoring/command/comment/add'
                        . ',icingadb/command/comment/add'
                        . ',monitoring/command/comment/delete'
                        . ',icingadb/command/comment/delete'
                        . ',monitoring/command/downtime/schedule'
                        . ',icingadb/command/downtime/schedule'
                        . ',monitoring/command/downtime/delete'
                        . ',icingadb/command/downtime/delete'
                        . ',monitoring/command/process-check-result'
                        . ',icingadb/command/process-check-result'
                        . ',monitoring/command/feature/instance'
                        . ',icingadb/command/feature/instance'
                        . ',monitoring/command/feature/object/active-checks'
                        . ',icingadb/command/feature/object/active-checks'
                        . ',monitoring/command/feature/object/passive-checks'
                        . ',icingadb/command/feature/object/passive-checks'
                        . ',monitoring/command/feature/object/notifications'
                        . ',icingadb/command/feature/object/notifications'
                        . ',monitoring/command/feature/object/event-handler'
                        . ',icingadb/command/feature/object/event-handler'
                        . ',monitoring/command/feature/object/flap-detection'
                        . ',icingadb/command/feature/object/flap-detection'
                        . ',monitoring/command/send-custom-notification'
                        . ',icingadb/command/send-custom-notification'
                ],
                'full-with-refusals' => [
                    'permissions'   => 'module/monitoring,monitoring/command/*,icingadb/command/*',
                    'refusals'      => 'monitoring/command/downtime/*'
                        . ',icingadb/command/downtime/*'
                        . ',monitoring/command/feature/instance'
                        . ',icingadb/command/feature/instance'
                ],
                'active-only' => [
                    'permissions'   => 'module/monitoring'
                        . ',monitoring/command/schedule-check/active-only'
                        . ',icingadb/command/schedule-check/active-only'
                ],
                'no-monitoring-contacts' => [
                    'permissions'               => 'module/monitoring,no-monitoring/contacts',
                    'icingadb/denylist/routes'  => 'users,usergroups'
                ],
                'reporting-only' => [
                    'permissions'   => 'module/reporting'
                ]
            ]
        ],
        'single-role-or-group' => [
            'initial' => [
                'one' => [
                    'groups'                    => 'support,helpdesk',
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
                ],
                'two' => [
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
                ]
            ],
            'expected' => [
                'one' => [
                    'groups'                    => 'support,helpdesk',
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo',
                    'icingadb/filter/objects'   => 'host.name=foo|hostgroup.name=foo'
                ],
                'two' => [
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
                ]
            ]
        ]
    ];

    protected $defaultConfigDir;

    protected $fileStorage;

    protected function setUp(): void
    {
        $this->defaultConfigDir = Config::$configDir;
        $this->fileStorage = new TemporaryLocalFileStorage();

        Config::$configDir = dirname($this->fileStorage->resolvePath('bogus'));
    }

    protected function tearDown(): void
    {
        Config::$configDir = $this->defaultConfigDir;
        unset($this->fileStorage); // Should clean up automatically
        Config::module('monitoring', 'config', true);
    }

    protected function getConfig(string $case): array
    {
        return [$this->config[$case]['initial'], $this->config[$case]['expected']];
    }

    protected function createConfig(string $path, array $data): void
    {
        $config = new Config(new ConfigObject($data));
        $config->saveIni($this->fileStorage->resolvePath($path));
    }

    protected function loadConfig(string $path): array
    {
        return Config::fromIni($this->fileStorage->resolvePath($path))->toArray();
    }

    protected function createCommandInstance(string ...$params): ToicingadbCommand
    {
        array_unshift($params, 'program');

        $app = $this->createConfiguredMock(Cli::class, [
            'getParams' => new Params($params),
            'getModuleManager' => $this->createConfiguredMock(Manager::class, [
                'loadEnabledModules' => null
            ])
        ]);

        return new ToicingadbCommand(
            $app,
            'migrate',
            'toicingadb',
            'dashboard',
            false
        );
    }

    /**
     * Checks the following:
     * - Whether only a single user is handled
     * - Whether backups are made
     * - Whether a second run changes nothing, if nothing changed
     * - Whether a second run keeps the backup, if nothing changed
     * - Whether a new backup isn't made, if nothing changed
     * - Whether existing Icinga DB dashboards are transformed regarding wildcard filters
     */
    public function testDashboardMigrationBehavesAsExpectedByDefault()
    {
        [$initialConfig, $expected] = $this->getConfig('dashboards');

        $this->createConfig('dashboards/test/dashboard.ini', $initialConfig);
        $this->createConfig('dashboards/test2/dashboard.ini', $initialConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->dashboardAction();

        $config = $this->loadConfig('dashboards/test/dashboard.ini');
        $this->assertSame($expected, $config);

        $config2 = $this->loadConfig('dashboards/test2/dashboard.ini');
        $this->assertSame($initialConfig, $config2);

        $backup = $this->loadConfig('dashboards/test/dashboard.backup.ini');
        $this->assertSame($initialConfig, $backup);

        $command = $this->createCommandInstance('--user', 'test');
        $command->dashboardAction();

        $configAfterSecondRun = $this->loadConfig('dashboards/test/dashboard.ini');
        $this->assertSame($config, $configAfterSecondRun);

        $backupAfterSecondRun = $this->loadConfig('dashboards/test/dashboard.backup.ini');
        $this->assertSame($backup, $backupAfterSecondRun);

        $backup1AfterSecondRun = $this->loadConfig('dashboards/test/dashboard.backup1.ini');
        $this->assertEmpty($backup1AfterSecondRun);
    }

    /**
     * Checks the following:
     * - Whether a second run creates a new backup, if something changed
     */
    public function testDashboardMigrationCreatesMultipleBackups()
    {
        $initialOldConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'monitoring/list/hosts?host_problem=1'
            ]
        ];
        $initialNewConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'icingadb/hosts?host.state.is_problem=y'
            ],
            'hosts.group_members' => [
                'title' => 'Group Members',
                'url'   => 'monitoring/list/hosts?hostgroup_name=group1|hostgroup_name=group2'
            ]
        ];
        $expectedNewConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'icingadb/hosts?host.state.is_problem=y'
            ]
        ];
        $expectedFinalConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'icingadb/hosts?host.state.is_problem=y'
            ],
            'hosts.group_members' => [
                'title' => 'Group Members',
                'url'   => 'icingadb/hosts?hostgroup.name=group1|hostgroup.name=group2'
            ]
        ];

        $this->createConfig('dashboards/test/dashboard.ini', $initialOldConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->dashboardAction();

        $newConfig = $this->loadConfig('dashboards/test/dashboard.ini');
        $this->assertSame($expectedNewConfig, $newConfig);
        $oldBackup = $this->loadConfig('dashboards/test/dashboard.backup.ini');
        $this->assertSame($initialOldConfig, $oldBackup);

        $this->createConfig('dashboards/test/dashboard.ini', $initialNewConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->dashboardAction();

        $finalConfig = $this->loadConfig('dashboards/test/dashboard.ini');
        $this->assertSame($expectedFinalConfig, $finalConfig);
        $newBackup = $this->loadConfig('dashboards/test/dashboard.backup1.ini');
        $this->assertSame($initialNewConfig, $newBackup);
    }

    /**
     * Checks the following:
     * - Whether backups are skipped
     */
    public function testDashboardMigrationSkipsBackupIfRequested()
    {
        [$initialConfig, $expected] = $this->getConfig('dashboards');

        $this->createConfig('dashboards/test/dashboard.ini', $initialConfig);

        $command = $this->createCommandInstance('--user', 'test', '--no-backup');
        $command->dashboardAction();

        $config = $this->loadConfig('dashboards/test/dashboard.ini');
        $this->assertSame($expected, $config);

        $backup = $this->loadConfig('dashboards/test/dashboard.backup.ini');
        $this->assertEmpty($backup);
    }

    /**
     * Checks the following:
     * - Whether multiple users are handled
     * - Whether multiple backups are made
     */
    public function testDashboardMigrationMigratesAllUsers()
    {
        [$initialConfig, $expected] = $this->getConfig('dashboards');

        $users = ['foo', 'bar', 'raboof'];

        foreach ($users as $user) {
            $this->createConfig("dashboards/$user/dashboard.ini", $initialConfig);
        }

        $command = $this->createCommandInstance('--user', '*');
        $command->dashboardAction();

        foreach ($users as $user) {
            $config = $this->loadConfig("dashboards/$user/dashboard.ini");
            $this->assertSame($expected, $config);

            $backup = $this->loadConfig("dashboards/$user/dashboard.backup.ini");
            $this->assertSame($initialConfig, $backup);
        }
    }

    public function testDashboardMigrationExpectsUserSwitch()
    {
        $this->markTestSkipped('The switch is not validated early enough');

        $this->expectException(MissingParameterException::class);
        $this->expectExceptionMessage('Required parameter \'user\' missing');

        $command = $this->createCommandInstance();
        $command->dashboardAction();
    }

    /**
     * Checks the following:
     * - Whether only a single user is handled
     * - Whether shared host actions are migrated, depending on the owner
     * - Whether old configs are kept
     * - Whether a second run changes nothing
     */
    public function testNavigationMigrationBehavesAsExpectedByDefault()
    {
        [$initialHostConfig, $expectedHosts] = $this->getConfig('host-actions');
        [$initialServiceConfig, $expectedServices] = $this->getConfig('service-actions');

        $this->createConfig('preferences/test/host-actions.ini', $initialHostConfig);
        $this->createConfig('preferences/test/service-actions.ini', $initialServiceConfig);
        $this->createConfig('preferences/test2/host-actions.ini', $initialHostConfig);
        $this->createConfig('preferences/test2/service-actions.ini', $initialServiceConfig);

        [$initialSharedConfig, $expectedShared] = $this->getConfig('shared-host-actions');
        $this->createConfig('navigation/host-actions.ini', $initialSharedConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $hosts = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $services = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($expectedHosts, $hosts);
        $this->assertSame($expectedServices, $services);

        $sharedConfig = $this->loadConfig('navigation/icingadb-host-actions.ini');
        $this->assertSame($expectedShared, $sharedConfig);

        $hosts2 = $this->loadConfig('preferences/test2/icingadb-host-actions.ini');
        $services2 = $this->loadConfig('preferences/test2/icingadb-service-actions.ini');
        $this->assertEmpty($hosts2);
        $this->assertEmpty($services2);

        $oldHosts = $this->loadConfig('preferences/test/host-actions.ini');
        $oldServices = $this->loadConfig('preferences/test/service-actions.ini');
        $this->assertSame($initialHostConfig, $oldHosts);
        $this->assertSame($initialServiceConfig, $oldServices);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $hostsAfterSecondRun = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $servicesAfterSecondRun = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($hosts, $hostsAfterSecondRun);
        $this->assertSame($services, $servicesAfterSecondRun);
    }

    /**
     * Checks the following:
     * - Whether existing Icinga DB Actions are transformed regarding wildcard filters
     */
    public function testNavigationMigrationTransformsAlreadyExistingIcingaDBActions()
    {
        [$initialHostConfig, $expectedHosts] = $this->getConfig('icingadb-host-actions');
        [$initialServiceConfig, $expectedServices] = $this->getConfig('icingadb-service-actions');

        $this->createConfig('preferences/test/icingadb-host-actions.ini', $initialHostConfig);
        $this->createConfig('preferences/test/icingadb-service-actions.ini', $initialServiceConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $hosts = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $services = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($expectedHosts, $hosts);
        $this->assertSame($expectedServices, $services);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $hostsAfterSecondRun = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $servicesAfterSecondRun = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($hosts, $hostsAfterSecondRun);
        $this->assertSame($services, $servicesAfterSecondRun);
    }

    /**
     * Checks the following:
     * - Whether legacy host/service macros are migrated
     */
    public function testNavigationMigrationMigratesLegacyMacros()
    {
        $this->markTestSkipped('Did not work in the previous implementation as well');

        [$initialHostConfig, $expectedHosts] = $this->getConfig('host-actions-legacy-macros');
        [$initialServiceConfig, $expectedServices] = $this->getConfig('service-actions-legacy-macros');

        $this->createConfig('preferences/test/host-actions.ini', $initialHostConfig);
        $this->createConfig('preferences/test/service-actions.ini', $initialServiceConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $hosts = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $services = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($expectedHosts, $hosts);
        $this->assertSame($expectedServices, $services);
    }

    /**
     * Checks the following:
     * - Whether old configs are removed
     */
    public function testNavigationMigrationDeletesOldConfigsIfRequested()
    {
        [$initialHostConfig, $expectedHosts] = $this->getConfig('host-actions');
        [$initialServiceConfig, $expectedServices] = $this->getConfig('service-actions');

        $this->createConfig('preferences/test/host-actions.ini', $initialHostConfig);
        $this->createConfig('preferences/test/service-actions.ini', $initialServiceConfig);

        $command = $this->createCommandInstance('--user', 'test', '--delete');
        $command->navigationAction();

        $hosts = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $services = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($expectedHosts, $hosts);
        $this->assertSame($expectedServices, $services);

        $oldHosts = $this->loadConfig('preferences/test/host-actions.ini');
        $oldServices = $this->loadConfig('preferences/test/service-actions.ini');
        $this->assertEmpty($oldHosts);
        $this->assertEmpty($oldServices);
    }

    /**
     * Checks the following:
     * - Whether existing configs are left alone by default
     * - Whether existing configs are overridden if requested
     */
    public function testNavigationMigrationOverridesExistingActionsIfRequested()
    {
        $initialOldConfig = [
            'hosts' => [
                'type'      => 'host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host_name=*foo*'
            ]
        ];
        $initialNewConfig = [
            'hosts' => [
                'type'      => 'icingadb-host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host.name~*bar*'
            ]
        ];
        $expectedFinalConfig = [
            'hosts' => [
                'type'      => 'icingadb-host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host.name~*foo*'
            ]
        ];

        $this->createConfig('preferences/test/host-actions.ini', $initialOldConfig);
        $this->createConfig('preferences/test/icingadb-host-actions.ini', $initialNewConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $finalConfig = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $this->assertSame($initialNewConfig, $finalConfig);

        $command = $this->createCommandInstance('--user', 'test', '--override');
        $command->navigationAction();

        $finalConfig = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $this->assertSame($expectedFinalConfig, $finalConfig);
    }

    public function testNavigationMigrationExpectsUserSwitch()
    {
        $this->markTestSkipped('The switch is not validated early enough');

        $this->expectException(MissingParameterException::class);
        $this->expectExceptionMessage('Required parameter \'user\' missing');

        $command = $this->createCommandInstance();
        $command->navigationAction();
    }

    /**
     * Checks the following:
     * - Whether only a single role is handled
     * - Whether role name matching works
     */
    public function testRoleMigrationHandlesASingleRoleOnlyIfRequested()
    {
        [$initialConfig, $expected] = $this->getConfig('single-role-or-group');

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', 'one');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expected, $config);
    }

    /**
     * Checks the following:
     * - Whether only a single role is handled
     * - Whether group matching works
     */
    public function testRoleMigrationHandlesARoleWithMatchingGroups()
    {
        [$initialConfig, $expected] = $this->getConfig('single-role-or-group');

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--group', 'support');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expected, $config);
    }

    /**
     * Checks the following:
     * - Whether permissions are properly migrated
     * - Whether refusals are properly migrated
     * - Whether restrictions are properly migrated
     * - Whether blacklists are properly migrated
     */
    public function testRoleMigrationMigratesAllRoles()
    {
        [$initialConfig, $expected] = $this->getConfig('all-roles');

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', '*');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expected, $config);
    }

    /**
     * Checks the following:
     * - Whether monitoring's variable protection rules are migrated to all roles granting access to monitoring
     */
    public function testRoleMigrationAlsoMigratesVariableProtections()
    {
        $initialConfig = [
            'one' => [
                'permissions' => 'module/monitoring'
            ],
            'two' => [
                'permissions' => 'module/monitoring'
            ],
            'three' => [
                'permissions' => 'module/reporting'
            ]
        ];
        $expectedConfig = [
            'one' => [
                'permissions'                   => 'module/monitoring',
                'icingadb/protect/variables'    => 'ob.*,env'
            ],
            'two' => [
                'permissions'                   => 'module/monitoring',
                'icingadb/protect/variables'    => 'ob.*,env'
            ],
            'three' => [
                'permissions'                   => 'module/reporting'
            ]
        ];

        $this->createConfig('modules/monitoring/config.ini', [
            'security' => [
                'protected_customvars' => 'ob.*,env'
            ]
        ]);

        // Invalidate config cache
        Config::module('monitoring', 'config', true);

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', '*');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expectedConfig, $config);
    }

    /**
     * Checks the following:
     * - Whether already migrated roles are skipped during migration
     * - Whether already migrated roles are transformed regarding wildcard filters
     */
    public function testRoleMigrationSkipsRolesThatAlreadyGrantAccessToIcingaDbButTransformWildcardRestrictions()
    {
        $initialConfig = [
            'only-monitoring' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*',
                'monitoring/filter/objects' => 'host_name=*foo*'
            ],
            'monitoring-and-icingadb' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*,module/icingadb',
                'monitoring/filter/objects' => 'host_name=*bar*',
                'icingadb/filter/objects' => 'host.name=*foo*'
            ]
        ];
        $expectedConfig = [
            'only-monitoring' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*,icingadb/command/comment/*',
                'monitoring/filter/objects' => 'host_name=*foo*',
                'icingadb/filter/objects' => 'host.name~*foo*'
            ],
            'monitoring-and-icingadb' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*,module/icingadb',
                'monitoring/filter/objects' => 'host_name=*bar*',
                'icingadb/filter/objects' => 'host.name~*foo*'
            ]
        ];

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', '*');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expectedConfig, $config);
    }

    /**
     * Checks the following:
     * - Whether already migrated roles are reset if requested
     */
    public function testRoleMigrationOverridesAlreadyMigratedRolesIfRequested()
    {
        $initialConfig = [
            'only-monitoring' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*',
                'monitoring/filter/objects' => 'host_name=*foo*'
            ],
            'monitoring-and-icingadb' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*,module/icingadb',
                'monitoring/filter/objects' => 'host_name=*bar*',
                'icingadb/filter/objects' => 'host.name=*foo*'
            ]
        ];
        $expectedConfig = [
            'only-monitoring' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*,icingadb/command/comment/*',
                'monitoring/filter/objects' => 'host_name=*foo*',
                'icingadb/filter/objects' => 'host.name~*foo*'
            ],
            'monitoring-and-icingadb' => [
                'permissions' => 'module/monitoring'
                    . ',monitoring/command/comment/*'
                    . ',icingadb/command/comment/*',
                'monitoring/filter/objects' => 'host_name=*bar*',
                'icingadb/filter/objects' => 'host.name~*bar*'
            ]
        ];

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', '*', '--override');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expectedConfig, $config);
    }

    public function testRoleMigrationExpectsTheRoleOrGroupSwitch()
    {
        $this->expectException(IcingaException::class);
        $this->expectExceptionMessage("One of the parameters 'group' or 'role' must be supplied");

        $command = $this->createCommandInstance();
        $command->roleAction();
    }

    public function testRoleMigrationExpectsEitherTheRoleOrGroupSwitchButNotBoth()
    {
        $this->expectException(IcingaException::class);
        $this->expectExceptionMessage("Use either 'group' or 'role'. Both cannot be used as role overrules group.");

        $command = $this->createCommandInstance('--role=foo', '--group=bar');
        $command->roleAction();
    }
}
