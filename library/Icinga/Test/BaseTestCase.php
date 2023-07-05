<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Test {

    use Exception;
    use Icinga\Web\Request;
    use Icinga\Web\Response;
    use ipl\I18n\NoopTranslator;
    use ipl\I18n\StaticTranslator;
    use RuntimeException;
    use Mockery;
    use Icinga\Application\Icinga;
    use Icinga\Data\ConfigObject;
    use Icinga\Data\ResourceFactory;
    use Icinga\Data\Db\DbConnection;

    /**
     * Class BaseTestCase
     */
    abstract class BaseTestCase extends Mockery\Adapter\Phpunit\MockeryTestCase implements DbTest
    {
        /**
         * Path to application/
         *
         * @var string
         * @deprecated Use Icinga::app()->getApplicationDir() instead
         */
        public static $appDir;

        /**
         * Path to library/Icinga
         *
         * @var string
         * @deprecated Use Icinga::app()->getLibraryDir('Icinga') instead
         */
        public static $libDir;

        /**
         * Path to etc/
         *
         * @var string
         * @deprecated Use Icinga::app()->getBaseDir('etc') instead
         */
        public static $etcDir;

        /**
         * Path to test/php/
         *
         * @var string
         * @deprecated Use Icinga::app()->getBaseDir('test/php') instead
         */
        public static $testDir;

        /**
         * Path to share/icinga2-web
         *
         * @var string
         * @deprecated Unused
         */
        public static $shareDir;

        /**
         * Path to modules/
         *
         * @var string
         * @deprecated Use Icinga::app()->getModuleManager()->getModuleDirs() instead
         */
        public static $moduleDir;

        /**
         * Resource configuration for different database types
         *
         * @var array
         */
        protected static $dbConfiguration = array(
            'mysql' => array(
                'type'      => 'db',
                'db'        => 'mysql',
                'host'      => '127.0.0.1',
                'port'      => 3306,
                'dbname'    => 'icinga_unittest',
                'username'  => 'icinga_unittest',
                'password'  => 'icinga_unittest'
            ),
            'pgsql' => array(
                'type'      => 'db',
                'db'        => 'pgsql',
                'host'      => '127.0.0.1',
                'port'      => 5432,
                'dbname'    => 'icinga_unittest',
                'username'  => 'icinga_unittest',
                'password'  => 'icinga_unittest'
            ),
        );

        /** @var Request */
        private $requestMock;

        /** @var Response */
        private $responseMock;

        /**
         * Setup MVC bootstrapping and ensure that the Icinga-Mock gets reinitialized
         */
        public function setUp(): void
        {
            parent::setUp();
            $this->setupRequestMock();
            $this->setupResponseMock();

            StaticTranslator::$instance = new NoopTranslator();
        }

        private function setupRequestMock()
        {
            $this->requestMock = Mockery::mock('Icinga\Web\Request')->shouldDeferMissing();
            $this->requestMock->shouldReceive('getPathInfo')->andReturn('')->byDefault()
                ->shouldReceive('getBaseUrl')->andReturn('/')->byDefault()
                ->shouldReceive('getQuery')->andReturn(array())->byDefault()
                ->shouldReceive('getParam')->with(Mockery::type('string'), Mockery::type('string'))
                ->andReturnUsing(function ($name, $default) {
                    return $default;
                })->byDefault();

            Icinga::app()->setRequest($this->requestMock);
        }

        private function setupResponseMock()
        {
            $this->responseMock = Mockery::mock('Icinga\Web\Response')->shouldDeferMissing();
            Icinga::app()->setResponse($this->responseMock);
        }

        /**
         * Return the currently active request mock object
         *
         * @return Request
         */
        public function getRequestMock()
        {
            return $this->requestMock;
        }

        /**
         * Return the currently active response mock object
         *
         * @return Response
         */
        public function getResponseMock()
        {
            return $this->responseMock;
        }

        /**
         * Create Config for database configuration
         *
         * @param   string $name
         *
         * @return  ConfigObject
         * @throws  RuntimeException
         */
        protected function createDbConfigFor($name)
        {
            if (array_key_exists($name, self::$dbConfiguration)) {
                $config = new ConfigObject(self::$dbConfiguration[$name]);

                $host = getenv(sprintf('ICINGAWEB_TEST_%s_HOST', strtoupper($name)));
                if ($host) {
                    $config['host'] = $host;
                }

                $port = getenv(sprintf('ICINGAWEB_TEST_%s_PORT', strtoupper($name)));
                if ($port) {
                    $config['port'] = $port;
                }

                return $config;
            }

            throw new RuntimeException('Configuration for database type not available: ' . $name);
        }

        /**
         * Creates an array of Icinga\Data\Db\DbConnection
         *
         * @param   string $name
         *
         * @return  array
         */
        protected function createDbConnectionFor($name)
        {
            try {
                $conn = ResourceFactory::createResource($this->createDbConfigFor($name));
            } catch (Exception $e) {
                $conn = $e->getMessage();
            }

            return array(
                array($conn)
            );
        }

        /**
         * PHPUnit provider for mysql
         *
         * @return DbConnection
         */
        public function mysqlDb()
        {
            return $this->createDbConnectionFor('mysql');
        }

        /**
         * PHPUnit provider for pgsql
         *
         * @return DbConnection
         */
        public function pgsqlDb()
        {
            return $this->createDbConnectionFor('pgsql');
        }

        /**
         * PHPUnit provider for oracle
         *
         * @return DbConnection
         */
        public function oracleDb()
        {
            return $this->createDbConnectionFor('oracle');
        }

        /**
         * Executes sql file by using the database connection
         *
         * @param   DbConnection      $resource
         * @param   string          $filename
         *
         * @throws  RuntimeException
         */
        public function loadSql(DbConnection $resource, $filename)
        {
            if (!is_file($filename)) {
                throw new RuntimeException(
                    'Sql file not found: ' . $filename . ' (test=' . $this->getName() . ')'
                );
            }

            $sqlData = file_get_contents($filename);

            if (!$sqlData) {
                throw new RuntimeException(
                    'Sql file is empty: ' . $filename . ' (test=' . $this->getName() . ')'
                );
            }

            $resource->getDbAdapter()->exec($sqlData);
        }

        /**
         * Setup provider for testcase
         *
         * @param   string|DbConnection|null $resource
         */
        public function setupDbProvider($resource)
        {
            if (!$resource instanceof DbConnection) {
                if (is_string($resource)) {
                    $this->markTestSkipped('Could not initialize provider: ' . $resource);
                } else {
                    $this->markTestSkipped('Could not initialize provider');
                }
                return;
            }

            $adapter = $resource->getDbAdapter();

            try {
                $adapter->getConnection();
            } catch (Exception $e) {
                $this->markTestSkipped('Could not connect to provider: '. $e->getMessage());
            }

            $tables = $adapter->listTables();
            foreach ($tables as $table) {
                $adapter->exec('DROP TABLE ' . $table . ';');
            }
        }

        /**
         * Add assertMatchesRegularExpression() method for phpunit >= 8.0 < 9.0 for compatibility with PHP 7.2.
         *
         * @TODO Remove once PHP 7.2 support is not needed for testing anymore.
         */
        public static function assertMatchesRegularExpression(
            string $pattern,
            string $string,
            string $message = ''
        ): void {
            if (method_exists(parent::class, 'assertMatchesRegularExpression')) {
                parent::assertMatchesRegularExpression($pattern, $string, $message);
            } else {
                static::assertRegExp($pattern, $string, $message);
            }
        }
    }
}
