<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Test;

// @codingStandardsIgnoreStart
require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';
require_once 'Zend/Db/Adapter/Pdo/Abstract.php';
require_once 'DbTest.php';
require_once 'FormTest.php';
// @codingStandardsIgnoreEnd

use \Exception;
use \RuntimeException;
use \Zend_Test_PHPUnit_ControllerTestCase;
use \Zend_Config;
use \Zend_Db_Adapter_Pdo_Abstract;
use \Zend_Db_Adapter_Pdo_Mysql;
use \Zend_Db_Adapter_Pdo_Pgsql;
use \Zend_Db_Adapter_Pdo_Oci;
use \Icinga\Application\DbAdapterFactory;
use \Icinga\User\Preferences;
use \Icinga\Web\Form;

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
     * DbAdapterFactory configuration for different database types
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
     * @param   string $name
     * @param   array  $data
     * @param   string $dataName
     * @see     PHPUnit_Framework_TestCase::__construct
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $tz = @date_default_timezone_get();

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
        $this->requireDbLibraries();

        try {
            $adapter = DbAdapterFactory::createDbAdapter($this->createDbConfigFor($name));
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
     * @param   Zend_Db_Adapter_Pdo_Abstract $resource
     * @param   string                       $filename
     *
     * @return  boolean Operational success flag
     * @throws RuntimeException
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
     * Instantiate a new form object
     *
     * @param   string $formClass      Form class to instantiate
     * @param   array  $requestData    Request data for the form
     *
     * @return  Form
     * @throws RuntimeException
     */
    public function createForm($formClass, array $requestData = array())
    {
        $this->requireFormLibraries();

        $classParts = explode('\\', $formClass);
        $identifier = array_shift($classParts);
        array_shift($classParts); // Throw away
        $fixedPathComponent = '/forms';

        if (strtolower($identifier) == 'icinga') {
            $startPathComponent = self::$appDir . $fixedPathComponent;
        } else {
            $startPathComponent = self::$moduleDir
                . '/'
                . strtolower($identifier)
                . '/application'
                .$fixedPathComponent;
        }

        $classFile = $startPathComponent . '/' . implode('/', $classParts) . '.php';

        if (!file_exists($classFile)) {
            throw new RuntimeException('Class file for form "' . $formClass . '" not found');
        }

        require_once $classFile;
        $form = new $formClass();
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

    /**
     * Require all libraries to instantiate forms
     */
    public function requireFormLibraries()
    {
        // @codingStandardsIgnoreStart
        require_once 'Zend/Form/Decorator/Abstract.php';
        require_once 'Zend/Validate/Abstract.php';
        require_once 'Zend/Form/Element/Xhtml.php';
        require_once 'Zend/Form/Element/Text.php';
        require_once 'Zend/Form/Element/Submit.php';
        require_once 'Zend/Form.php';
        require_once 'Zend/View.php';

        require_once self::$libDir . '/Web/Form/InvalidCSRFTokenException.php';

        require_once self::$libDir . '/Web/Form/Element/DateTimePicker.php';
        require_once self::$libDir . '/Web/Form/Element/Note.php';
        require_once self::$libDir . '/Web/Form/Element/Number.php';

        require_once self::$libDir . '/Web/Form/Decorator/ConditionalHidden.php';
        require_once self::$libDir . '/Web/Form/Decorator/HelpText.php';

        require_once self::$libDir . '/Web/Form/Validator/DateFormatValidator.php';
        require_once self::$libDir . '/Web/Form/Validator/TimeFormatValidator.php';
        require_once self::$libDir . '/Web/Form/Validator/WritablePathValidator.php';

        require_once self::$libDir . '/Web/Form.php';

        require_once self::$libDir . '/User/Preferences.php';
        // @codingStandardsIgnoreEnd
    }

    /**
     * Require all classes for database adapter creation
     */
    public function requireDbLibraries()
    {
        // @codingStandardsIgnoreStart

        require_once 'Zend/Config.php';
        require_once 'Zend/Db.php';
        require_once 'Zend/Log.php';

        require_once realpath(self::$libDir . '/Exception/ConfigurationError.php');
        require_once realpath(self::$libDir . '/Util/ConfigAwareFactory.php');
        require_once realpath(self::$libDir . '/Application/DbAdapterFactory.php');
        require_once realpath(self::$libDir . '/Application/Logger.php');

        // @codingStandardsIgnoreEnd
    }
}

// @codingStandardsIgnoreStart
BaseTestCase::setupDirectories();
// @codingStandardsIgnoreEnd