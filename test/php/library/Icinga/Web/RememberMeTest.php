<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Web;

use Icinga\Test\BaseTestCase;
use Icinga\Web\RememberMe;
use ipl\Sql\Config;
use ipl\Sql\Connection;
use ipl\Sql\Select;
use LogicException;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class RememberMeTest extends BaseTestCase
{
    protected Connection $db;

    public function tearDown(): void
    {
        try {
            if (isset($this->db)) {
                $this->db->prepexec('DROP TABLE ' . RememberMeWithTestDatabase::TABLE);
            }
        } finally {
            RememberMeWithTestDatabase::$connection = null;
            unset($this->db);
            parent::tearDown();
        }
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function distinctUsernames(): iterable
    {
        $pairs = [
            'different users' => ['alice@one', 'bob@one'],
            'accent in domain' => ['alice@one', 'alice@óne'],
            'accent in username' => ['alice@one', 'álice@one'],
            'trailing space' => ['alice@one', 'alice@one '],
            'query metacharacters' => ["o'reilly' OR 1=1 --", 'bob@one'],
            'wildcard characters' => ['alice_%?@one', 'alice_12x@one'],
        ];

        foreach (['mysql', 'pgsql'] as $engine) {
            foreach ($pairs as $name => [$username, $otherUsername]) {
                yield "$engine: $name" => [$engine, $username, $otherUsername];
            }
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function databases(): iterable
    {
        yield 'mysql' => ['mysql'];
        yield 'pgsql' => ['pgsql'];
    }

    #[DataProvider('distinctUsernames')]
    public function testUsernameOperationsKeepDistinctOwnersSeparate(
        string $engine,
        string $username,
        string $otherUsername
    ): void {
        $this->setUpDatabase($engine);
        $sharedIv = str_repeat('a', 32);
        $ownIv = str_repeat('b', 32);
        $otherIv = str_repeat('c', 32);
        $secondOwnIv = str_repeat('d', 32);
        $this->insertDevice($username, $sharedIv, 'Own laptop');
        $this->insertDevice($username, $ownIv, 'Own phone');
        $this->insertDevice($username, $secondOwnIv, 'Own tablet');
        $this->insertDevice($otherUsername, $sharedIv, 'Other laptop');
        $this->insertDevice($otherUsername, $otherIv, 'Other phone');
        $initialRows = $this->storedDevices();
        $expectedUsernames = [strtolower($username), strtolower($otherUsername)];
        sort($expectedUsernames);
        $usernames = array_map(
            static fn(stdClass $row): string => $row->username,
            RememberMeWithTestDatabase::getAllUser()
        );
        sort($usernames);
        $this->assertSame($expectedUsernames, $usernames);

        $this->assertSame([
            [$username, $sharedIv, 'Own laptop'],
            [$username, $ownIv, 'Own phone'],
            [$username, $secondOwnIv, 'Own tablet'],
        ], $this->listedDevices($username));
        $this->assertSame([
            [$otherUsername, $sharedIv, 'Other laptop'],
            [$otherUsername, $otherIv, 'Other phone'],
        ], $this->listedDevices($otherUsername));
        $this->assertSame([], $this->listedDevices('missing@one'));

        $rememberMe = new RememberMeWithTestDatabase();
        $rememberMe->removeByUsername($username, $otherIv);
        $this->assertSame($initialRows, $this->storedDevices());
        $rememberMe->removeByUsername($username, "' OR 1=1 --");
        $this->assertSame($initialRows, $this->storedDevices());

        // The same fingerprint can belong to two users because the schema has no unique constraint on it.
        $rememberMe->removeByUsername($username, $sharedIv);
        $this->assertSame($this->sortedDevices([
            [$username, $ownIv, 'Own phone'],
            [$username, $secondOwnIv, 'Own tablet'],
            [$otherUsername, $sharedIv, 'Other laptop'],
            [$otherUsername, $otherIv, 'Other phone'],
        ]), $this->storedDevices());
    }

    #[DataProvider('databases')]
    public function testListingIncludesEveryAsciiCaseAlias(string $engine): void
    {
        $this->setUpCaseAliases($engine);

        foreach (['alice@one', 'Alice@ONE', 'ALICE@One', 'aLiCe@oNe'] as $alias) {
            $this->assertSame([
                [$alias, str_repeat('a', 32), 'Own laptop'],
                [$alias, str_repeat('b', 32), 'Own phone'],
                [$alias, str_repeat('c', 32), 'Own tablet'],
            ], $this->listedDevices($alias));
        }
    }

    #[DataProvider('databases')]
    public function testScopedRemovalUsesCaseAliasesAndProtectsOtherOwners(string $engine): void
    {
        $this->setUpCaseAliases($engine);
        $rememberMe = new RememberMeWithTestDatabase();
        $initialRows = $this->storedDevices();

        $rememberMe->removeByUsername('aLiCe@oNe', str_repeat('d', 32));
        $this->assertSame($initialRows, $this->storedDevices());
        $rememberMe->removeByUsername('aLiCe@oNe', str_repeat('a', 32));

        $this->assertSame($this->sortedDevices([
            ['alice@one', str_repeat('b', 32), 'Own phone'],
            ['ALICE@One', str_repeat('c', 32), 'Own tablet'],
            ['bob@one', str_repeat('a', 32), 'Other laptop'],
            ['bob@one', str_repeat('d', 32), 'Other phone'],
            ['alice@óne', str_repeat('e', 32), 'Accented domain'],
            ['álice@one', str_repeat('f', 32), 'Accented username'],
        ]), $this->storedDevices());
    }

    #[DataProvider('databases')]
    public function testNormalizedOwnersAreUniqueStringsAndRoundTripToTheirDevices(string $engine): void
    {
        $this->setUpCaseAliases($engine);
        $this->insertDevice('alice@one ', str_repeat('g', 32), 'Trailing space');
        $this->insertDevice("o'reilly' OR 1=1 --", str_repeat('h', 32), 'Query metacharacters');
        $expectedDevices = [
            'alice@one' => [
                ['alice@one', str_repeat('a', 32), 'Own laptop'],
                ['alice@one', str_repeat('b', 32), 'Own phone'],
                ['alice@one', str_repeat('c', 32), 'Own tablet'],
            ],
            'alice@one ' => [['alice@one ', str_repeat('g', 32), 'Trailing space']],
            'alice@óne' => [['alice@óne', str_repeat('e', 32), 'Accented domain']],
            'álice@one' => [['álice@one', str_repeat('f', 32), 'Accented username']],
            'bob@one' => [
                ['bob@one', str_repeat('a', 32), 'Other laptop'],
                ['bob@one', str_repeat('d', 32), 'Other phone'],
            ],
            "o'reilly' or 1=1 --" => [["o'reilly' or 1=1 --", str_repeat('h', 32), 'Query metacharacters']],
        ];
        $expectedOwners = array_keys($expectedDevices);
        sort($expectedOwners);
        $owners = [];
        foreach (RememberMeWithTestDatabase::getAllUser() as $row) {
            $owners[] = $row->username;
        }

        sort($owners);
        $this->assertSame($expectedOwners, $owners);
        foreach ($owners as $owner) {
            $this->assertSame($expectedDevices[$owner], $this->listedDevices($owner));
        }
    }

    #[DataProvider('databases')]
    public function testGlobalRemovalDeletesTheSelectedFingerprintAcrossUsers(string $engine): void
    {
        $this->setUpDatabase($engine);
        $sharedIv = str_repeat('a', 32);
        $otherIv = str_repeat('b', 32);
        $this->insertDevice('alice@one', $sharedIv, 'Alice laptop');
        $this->insertDevice('bob@one', $sharedIv, 'Bob laptop');
        $this->insertDevice('bob@one', $otherIv, 'Bob phone');

        (new RememberMeWithTestDatabase())->remove($sharedIv);

        $this->assertSame([
            ['bob@one', $otherIv, 'Bob phone'],
        ], $this->storedDevices());
    }

    #[DataProvider('databases')]
    public function testFingerprintComparisonRemainsCaseSensitive(string $engine): void
    {
        $this->setUpDatabase($engine);
        $lowercaseIv = str_repeat('a', 32);
        $uppercaseIv = str_repeat('A', 32);
        $this->insertDevice('alice@one', $lowercaseIv, 'Laptop');
        $this->insertDevice('alice@one', $uppercaseIv, 'Phone');

        (new RememberMeWithTestDatabase())->removeByUsername('alice@one', $lowercaseIv);

        $this->assertSame([
            ['alice@one', $uppercaseIv, 'Phone'],
        ], $this->storedDevices());
    }

    protected function setUpCaseAliases(string $engine): void
    {
        $this->setUpDatabase($engine);
        $this->insertDevice('Alice@ONE', str_repeat('a', 32), 'Own laptop');
        $this->insertDevice('alice@one', str_repeat('b', 32), 'Own phone');
        $this->insertDevice('ALICE@One', str_repeat('c', 32), 'Own tablet');
        $this->insertDevice('bob@one', str_repeat('a', 32), 'Other laptop');
        $this->insertDevice('bob@one', str_repeat('d', 32), 'Other phone');
        $this->insertDevice('alice@óne', str_repeat('e', 32), 'Accented domain');
        $this->insertDevice('álice@one', str_repeat('f', 32), 'Accented username');
    }

    protected function setUpDatabase(string $engine): void
    {
        $config = new Config(static::createDbConfigFor($engine));
        if ($engine === 'mysql') {
            $config->charset = 'utf8mb4';
        }
        $config->options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ];
        $db = new Connection($config);
        try {
            $db->connect();
        } catch (PDOException $e) {
            $this->markTestSkipped('Could not connect to provider: ' . $e->getMessage());
        }

        // Temporary tables isolate the fixture from application tables and other test connections.
        if ($engine === 'mysql') {
            $db->prepexec('CREATE TEMPORARY TABLE ' . RememberMeWithTestDatabase::TABLE . ' (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(254) COLLATE utf8mb4_unicode_ci NOT NULL,
                random_iv VARCHAR(32) COLLATE utf8mb4_bin NOT NULL,
                http_user_agent TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin');
        } else {
            $db->prepexec('CREATE TEMPORARY TABLE ' . RememberMeWithTestDatabase::TABLE . ' (
                id SERIAL PRIMARY KEY,
                username VARCHAR(254) NOT NULL,
                random_iv VARCHAR(32) NOT NULL,
                http_user_agent TEXT NOT NULL
            )');
        }

        $this->db = $db;
        RememberMeWithTestDatabase::$connection = $db;
    }

    protected function insertDevice(string $username, string $iv, string $agent): void
    {
        $this->db->insert(RememberMeWithTestDatabase::TABLE, [
            'username' => $username,
            'random_iv' => $iv,
            'http_user_agent' => $agent,
        ]);
    }

    /**
     * @return list<array{string, string, string}>
     * @phpstan-impure
     */
    protected function storedDevices(): array
    {
        $rows = $this->db->select((new Select())
            ->from(RememberMeWithTestDatabase::TABLE)
            ->columns(['username', 'random_iv', 'http_user_agent']))->fetchAll();

        return $this->sortedDevices(array_map(
            static fn(stdClass $row): array => [$row->username, $row->random_iv, $row->http_user_agent],
            $rows
        ));
    }

    /**
     * @return list<array{string, string, string}>
     */
    protected function listedDevices(string $username): array
    {
        return $this->sortedDevices(array_map(
            static fn(stdClass $row): array => [$username, $row->random_iv, $row->http_user_agent],
            RememberMeWithTestDatabase::getAllByUsername($username)
        ));
    }

    /**
     * @param list<array{string, string, string}> $devices
     *
     * @return list<array{string, string, string}>
     */
    protected function sortedDevices(array $devices): array
    {
        sort($devices);

        return $devices;
    }
}

// Keep the database adapter with the regression tests that use it.
// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class RememberMeWithTestDatabase extends RememberMe
{
    /** @var string */
    public const TABLE = 'rememberme_username_test';

    public static ?Connection $connection = null;

    protected function getDb(): Connection
    {
        return static::$connection ?? throw new LogicException('Test database is not initialized');
    }

    protected function hasDb(): bool
    {
        return static::$connection !== null;
    }
}
