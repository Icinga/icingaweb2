<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Application;

use Icinga\Application\MigrationManager;
use Icinga\Module\Setup\Utils\DbTool;
use Icinga\Test\BaseTestCase;
use ipl\Sql\Connection;
use ReflectionMethod;
use ReflectionProperty;

class MigrationManagerTest extends BaseTestCase
{
    /** @var array<string, string> A database resource which requires a TLS client certificate */
    private static $tlsResource = [
        'db'        => 'mysql',
        'host'      => 'localhost',
        'port'      => '3306',
        'dbname'    => 'icingaweb2',
        'username'  => 'icingaweb2',
        'password'  => 'secret',
        'use_ssl'   => '1',
        'ssl_ca'    => '/etc/icingaweb2/ssl/ca.crt',
        'ssl_cert'  => '/etc/icingaweb2/ssl/client.crt',
        'ssl_key'   => '/etc/icingaweb2/ssl/client.key'
    ];

    public function testDbToolIsGivenTheTlsSettingsOfTheResource()
    {
        $config = $this->createDbToolConfig(self::$tlsResource);

        $this->assertSame('1', $config['use_ssl'], 'DbTool has not been told to use TLS');
        $this->assertSame('/etc/icingaweb2/ssl/ca.crt', $config['ssl_ca']);
        $this->assertSame('/etc/icingaweb2/ssl/client.crt', $config['ssl_cert']);
        $this->assertSame('/etc/icingaweb2/ssl/client.key', $config['ssl_key']);
    }

    public function testDbToolIsGivenTheRemainingTlsSettingsOfTheResource()
    {
        $config = $this->createDbToolConfig(array_merge(self::$tlsResource, [
            'ssl_capath'                    => '/etc/icingaweb2/ssl',
            'ssl_cipher'                    => 'ECDHE-RSA-AES256-GCM-SHA384',
            'ssl_do_not_verify_server_cert' => '1'
        ]));

        $this->assertSame('/etc/icingaweb2/ssl', $config['ssl_capath']);
        $this->assertSame('ECDHE-RSA-AES256-GCM-SHA384', $config['ssl_cipher']);
        $this->assertSame('1', $config['ssl_do_not_verify_server_cert']);
    }

    public function testDbToolIsGivenAClientCertificateWithoutACertificateAuthority()
    {
        $resource = self::$tlsResource;
        unset($resource['ssl_ca']);
        $resource['ssl_do_not_verify_server_cert'] = '1';

        $config = $this->createDbToolConfig($resource);

        $this->assertSame('1', $config['use_ssl']);
        $this->assertSame('/etc/icingaweb2/ssl/client.crt', $config['ssl_cert']);
        $this->assertSame('/etc/icingaweb2/ssl/client.key', $config['ssl_key']);
        $this->assertSame('1', $config['ssl_do_not_verify_server_cert']);
        $this->assertNull($config['ssl_ca'], 'A missing ssl_ca must not be missing from the config');
    }

    public function testDbToolIsGivenAllTlsSettingsEvenIfTheResourceDefinesNone()
    {
        $config = $this->createDbToolConfig([
            'db'        => 'mysql',
            'host'      => 'localhost',
            'port'      => '3306',
            'dbname'    => 'icingaweb2',
            'username'  => 'icingaweb2',
            'password'  => 'secret'
        ]);

        // DbTool accesses these unconditionally once use_ssl is set, so they must never be missing
        foreach (['use_ssl', 'ssl_key', 'ssl_cert', 'ssl_ca', 'ssl_capath', 'ssl_cipher'] as $name) {
            $this->assertArrayHasKey($name, $config);
            $this->assertNull($config[$name]);
        }

        $this->assertArrayHasKey('ssl_do_not_verify_server_cert', $config);
        $this->assertNull($config['ssl_do_not_verify_server_cert']);
    }

    public function testDbToolIsGivenTheConnectionParametersOfTheResource()
    {
        $config = $this->createDbToolConfig(self::$tlsResource);

        $this->assertSame('mysql', $config['db']);
        $this->assertSame('localhost', $config['host']);
        $this->assertSame('3306', $config['port']);
        $this->assertSame('icingaweb2', $config['dbname']);
        $this->assertSame('icingaweb2', $config['username']);
        $this->assertSame('secret', $config['password']);
    }

    /**
     * Get the configuration the migration manager passes to DbTool for the given resource
     *
     * @param array<string, string> $resource
     *
     * @return array<string, mixed>
     */
    private function createDbToolConfig(array $resource): array
    {
        $createDbTool = new ReflectionMethod(MigrationManager::class, 'createDbTool');
        $createDbTool->setAccessible(true);

        /** @var DbTool $tool */
        $tool = $createDbTool->invoke(MigrationManager::instance(), new Connection($resource));

        $config = new ReflectionProperty(DbTool::class, 'config');
        $config->setAccessible(true);

        return $config->getValue($tool);
    }
}
