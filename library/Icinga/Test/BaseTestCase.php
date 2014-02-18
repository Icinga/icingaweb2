<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace {

    if (!function_exists('t')) {
        function t()
        {
            return func_get_arg(0);
        }
    }

    if (!function_exists('mt')) {
        function mt()
        {
            return func_get_arg(0);
        }
    }
}

namespace Icinga\Test {

    use \Exception;
    use \RuntimeException;
    use Zend_Test_PHPUnit_ControllerTestCase;
    use Zend_Config;
    use Zend_Db_Adapter_Pdo_Abstract;
    use Zend_Db_Adapter_Pdo_Mysql;
    use Zend_Db_Adapter_Pdo_Pgsql;
    use Zend_Db_Adapter_Pdo_Oci;
    use Icinga\Data\ResourceFactory;
    use Icinga\User\Preferences;
    use Icinga\Web\Form;

    /**
     * Class BaseTestCase
     */
    class BaseTestCase extends Zend_Test_PHPUnit_ControllerTestCase implements DbTest, FormTest
    {
        /**
         * Path to application/
         *
         * @var string
         */
        public static $appDir;

        /**
         * Path to library/Icinga
         *
         * @var string
         */
        public static $libDir;

        /**
         * Path to etc/
         *
         * @var
         */
        public static $etcDir;

        /**
         * Path to test/php/
         *
         * @var string
         */
        public static $testDir;

        /**
         * Path to share/icinga2-web
         *
         * @var string
         */
        public static $shareDir;

        /**
         * Path to modules/
         *
         * @var string
         */
        public static $moduleDir;

        /**
         * Resource configuration for different database types
         *
         * @var array
         */
        private static $dbConfiguration = array(
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

        /**
         * Constructs a test case with the given name.
         *
         * @param   string  $name
         * @param   array   $data
         * @param   string  $dataName
         * @see     PHPUnit_Framework_TestCase::__construct
         */
        public function __construct($name = null, array $data = array(), $dataName = '')
        {
            parent::__construct($name, $data, $dataName);
            date_default_timezone_set('UTC');
        }

        /**
         * Setup test path environment
         *
         * @throws RuntimeException
         */
        public static function setupDirectories()
        {
            static $initialized = false;

            if ($initialized === true) {
                return;
            }

            $baseDir = realpath(__DIR__ . '/../../../');

            if ($baseDir === false) {
                throw new RuntimeException('Application base dir not found');
            }

            self::$appDir = $baseDir . '/application';
            self::$libDir = $baseDir . '/library/Icinga';
            self::$etcDir = $baseDir . '/etc';
            self::$testDir = $baseDir . '/test/php';
            self::$shareDir = $baseDir . '/share/icinga2-web';
            self::$moduleDir = $baseDir . '/modules';

            $initialized = true;
        }

        /**
         * Create Zend_Config for database configuration
         *
         * @param   string $name
         *
         * @return  Zend_Config
         * @throws  RuntimeException
         */
        private function createDbConfigFor($name)
        {
            if (array_key_exists($name, self::$dbConfiguration)) {
                return new Zend_Config(self::$dbConfiguration[$name]);
            }

            throw new RuntimeException('Configuration for database type not available: ' . $name);
        }

        /**
         * Creates an array of Zend Database Adapter
         *
         * @param   string $name
         *
         * @return  array
         */
        private function createDbAdapterFor($name)
        {
            try {
                $adapter = ResourceFactory::createResource($this->createDbConfigFor($name))->getConnection();
            } catch (Exception $e) {
                $adapter = $e->getMessage();
            }

            return array(
                array($adapter)
            );
        }

        /**
         * PHPUnit provider for mysql
         *
         * @return Zend_Db_Adapter_Pdo_Mysql
         */
        public function mysqlDb()
        {
            return $this->createDbAdapterFor('mysql');
        }

        /**
         * PHPUnit provider for pgsql
         *
         * @return Zend_Db_Adapter_Pdo_Pgsql
         */
        public function pgsqlDb()
        {
            return $this->createDbAdapterFor('pgsql');
        }

        /**
         * PHPUnit provider for oracle
         *
         * @return Zend_Db_Adapter_Pdo_Oci
         */
        public function oracleDb()
        {
            return $this->createDbAdapterFor('oracle');
        }

        /**
         * Executes sql file on PDO object
         *
         * @param   Zend_Db_Adapter_Pdo_Abstract    $resource
         * @param   string                          $filename
         *
         * @return  boolean Operational success flag
         * @throws  RuntimeException
         */
        public function loadSql(Zend_Db_Adapter_Pdo_Abstract $resource, $filename)
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

            $resource->exec($sqlData);
        }

        /**
         * Setup provider for testcase
         *
         * @param   string|Zend_Db_Adapter_PDO_Abstract|null $resource
         */
        public function setupDbProvider($resource)
        {
            if (!$resource instanceof Zend_Db_Adapter_Pdo_Abstract) {
                if (is_string($resource)) {
                    $this->markTestSkipped('Could not initialize provider: ' . $resource);
                } else {
                    $this->markTestSkipped('Could not initialize provider');
                }
                return;
            }

            try {
                $resource->getConnection();
            } catch (Exception $e) {
                $this->markTestSkipped('Could not connect to provider: '. $e->getMessage());
            }

            $tables = $resource->listTables();
            foreach ($tables as $table) {
                $resource->exec('DROP TABLE ' . $table . ';');
            }
        }

        /**
         * Instantiate a form
         *
         * If the form has CSRF protection enabled, creates the form's token element and adds the generated token to the
         * request data
         *
         * @param   string  $formClass      Qualified class name of the form to create. Note that the class has to be
         *                                  defined as no attempt is made to require the class before instantiating.
         * @param   array   $requestData    Request data for the form
         *
         * @return  Form
         * @throws  RuntimeException
         */
        public function createForm($formClass, array $requestData = array())
        {
            $form = new $formClass;
            // If the form has CSRF protection enabled, add the token to the request data, else all calls to
            // isSubmittedAndValid will fail
            $form->initCsrfToken();
            $token = $form->getValue($form->getTokenElementName());
            if ($token !== null) {
                $requestData[$form->getTokenElementName()] = $token;
            }
            $request = $this->getRequest();
            $request->setMethod('POST');
            $request->setPost($requestData);
            $form->setRequest($request);
            $form->setUserPreferences(
                new Preferences(
                    array()
                )
            );
            return $form;
        }
    }

    BaseTestCase::setupDirectories();
}
