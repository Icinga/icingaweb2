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

namespace Test\Icinga\Form\Config;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
// @codingStandardsIgnoreEnd

use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once 'Zend/Form.php';
require_once 'Zend/Config.php';
require_once 'Zend/Config/Ini.php';
require_once BaseTestCase::$libDir . '/Web/Form.php';
require_once BaseTestCase::$appDir . '/forms/Config/AuthenticationForm.php';
require_once BaseTestCase::$appDir . '/forms/Config/Authentication/BaseBackendForm.php';
require_once BaseTestCase::$appDir . '/forms/Config/Authentication/DbBackendForm.php';
require_once BaseTestCase::$appDir . '/forms/Config/Authentication/LdapBackendForm.php';
// @codingStandardsIgnoreEnd

use \Icinga\Web\Form;
use \DOMDocument;
use \Zend_Config;
use \Zend_View;

/**
 * Test for the authentication provider form
 *
 */
class AuthenticationFormTest extends BaseTestCase
{

    /**
     * Return a test configuration containing a database and a ldap backend
     *
     * @return Zend_Config
     */
    private function getTestConfig()
    {
        return new Zend_Config(
            array(
                'test-db' => array(
                    'backend'   =>  'db',
                    'target'    =>  'user',
                    'resource'  =>  'db_resource'
                ),
                'test-ldap' => array(
                    'backend' => 'ldap',
                    'target' => 'user',
                    'hostname' => 'test host',
                    'root_dn' => 'ou=test,dc=icinga,dc=org',
                    'bind_dn' => 'cn=testuser,cn=config',
                    'bind_pw' => 'password',
                    'user_class' => 'testClass',
                    'user_name_attribute' => 'testAttribute'
                )
            )
        );
    }

    /**
     * Test the ldap provider form population from config
     *
     */
    public function testLdapProvider()
    {
        $this->requireFormLibraries();
        $form = $this->createForm('Icinga\Form\Config\AuthenticationForm');
        $config = new Zend_Config(
            array(
                'test-ldap' => array(
                    'backend' => 'ldap',
                    'target' => 'user',
                    'hostname' => 'test host',
                    'root_dn' => 'ou=test,dc=icinga,dc=org',
                    'bind_dn' => 'cn=testuser,cn=config',
                    'bind_pw' => 'password',
                    'user_class' => 'testClass',
                    'user_name_attribute' => 'testAttribute'
                )
            )
        );
        $form->setConfiguration($config);
        $form->create();

        // parameters to be hidden
        $notShown = array('backend', 'target');
        foreach ($config->get('test-ldap')->toArray() as $name => $value) {
            if (in_array($name, $notShown)) {
                continue;
            }
            $this->assertEquals(
                $value,
                $form->getValue('backend_testldap_' . $name),
                'Asserting the ' . $name . ' parameter to be correctly populated for a ldap authentication form'
            );
        }
    }

    /**
     * Test the database provider form population from config
     *
     */
    public function testDbProvider()
    {
        $this->requireFormLibraries();
        $form = $this->createForm('Icinga\Form\Config\AuthenticationForm');
        $config = new Zend_Config(
            array(
                'test-db' => array(
                    'backend'   =>  'db',
                    'target'    =>  'user',
                    'resource'  =>  'db_resource'
                )
            )
        );
        $form->setResources(
            array(
                'db_resource' => array(
                    'type'      => 'db'
                )
            )
        );

        $form->setConfiguration($config);
        $form->create();

        // parameters to be hidden
        $notShown = array('backend', 'target');
        foreach ($config->get('test-db')->toArray() as $name => $value) {
            if (in_array($name, $notShown)) {
                continue;
            }
            $this->assertEquals(
                $value,
                $form->getValue('backend_testdb_' . $name),
                'Asserting the ' . $name . ' parameter to be correctly populated for a db authentication form'
            );
        }
    }

    /**
     * Test whether order modifications via 'priority' are considered
     *
     */
    public function testShowModifiedOrder()
    {
        $this->requireFormLibraries();
        $form = $this->createForm(
            'Icinga\Form\Config\AuthenticationForm',
            array(
                'priority' => 'test-ldap,test-db'
            )
        );
        $config = $this->getTestConfig();
        $form->setResources(
            array(
                'db_resource' => array(
                    'type' => 'db'
                )
            )
        );

        $form->setConfiguration($config);
        $form->create();

        $prio = array_keys($form->getConfig());
        $this->assertEquals('test-ldap', $prio[0], "Asserting priority changes to be persisted");
        $this->assertEquals('test-db', $prio[1], "Asserting priority changes to be persisted");
    }

    /**
     * Test whether configuration changes are correctly returned when calling getConfig
     *
     */
    public function testConfigurationCreation()
    {
        $this->requireFormLibraries();
        $form = $this->createForm(
            'Icinga\Form\Config\AuthenticationForm',
            array(
                'priority'                              =>  'test-ldap,test-db',
                'backend_testdb_resource'               =>  'db_resource_2',
                'backend_testldap_hostname'             =>  'modified_host',
                'backend_testldap_root_dn'              =>  'modified_root_dn',
                'backend_testldap_bind_dn'              =>  'modified_bind_dn',
                'backend_testldap_bind_pw'              =>  'modified_bind_pw',
                'backend_testldap_user_class'           =>  'modified_user_class',
                'backend_testldap_user_name_attribute'  =>  'modified_user_name_attribute'
            )
        );

        $form->setResources(
            array(
                'db_resource'   =>  array(
                    'type' => 'db'
                ),
                'db_resource_2' =>  array(
                    'type' => 'db'
                )
            )
        );

        $form->setConfiguration($this->getTestConfig());
        $form->create();

        $modified = new Zend_Config($form->getConfig());
        $this->assertEquals(
            'db_resource_2',
            $modified->get('test-db')->resource,
            'Asserting database resource modifications to be applied'
        );
        $this->assertEquals(
            'user',
            $modified->get('test-db')->target,
            'Asserting database target still being user when modifying'
        );
        $this->assertEquals(
            'db',
            $modified->get('test-db')->backend,
            'Asserting database backend still being db when modifying'
        );

        $ldap = $modified->get('test-ldap');
        $this->assertEquals(
            'modified_host',
            $ldap->hostname,
            'Asserting hostname modifications to be applied when modifying ldap authentication backends'
        );

        $this->assertEquals(
            'modified_root_dn',
            $ldap->root_dn,
            'Asserting root dn modifications to be applied when modifying ldap authentication backends'
        );

        $this->assertEquals(
            'modified_bind_dn',
            $ldap->bind_dn,
            'Asserting bind dn modifications to be applied when modifying ldap authentication backends'
        );

        $this->assertEquals(
            'modified_bind_pw',
            $ldap->bind_pw,
            'Asserting bind pw modifications to be applied when modifying ldap authentication backends'
        );

        $this->assertEquals(
            'modified_user_class',
            $ldap->user_class,
            'Asserting user class modifications to be applied when modifying ldap authentication backends'
        );

        $this->assertEquals(
            'modified_user_name_attribute',
            $ldap->user_name_attribute,
            'Asserting user name attribute modifications to be applied when modifying ldap authentication backends'
        );
    }

    /**
     * Test correct behaviour when ticking the 'remove backend' option
     */
    public function testBackendRemoval()
    {
        $this->requireFormLibraries();
        $form = $this->createForm(
            'Icinga\Form\Config\AuthenticationForm',
            array(
                'priority'                              =>  'test-ldap,test-db',
                'backend_testdb_resource'               =>  'db_resource_2',
                'backend_testldap_remove'               =>  1,
                'backend_testldap_hostname'             =>  'modified_host',
                'backend_testldap_root_dn'              =>  'modified_root_dn',
                'backend_testldap_bind_dn'              =>  'modified_bind_dn',
                'backend_testldap_bind_pw'              =>  'modified_bind_pw',
                'backend_testldap_user_class'           =>  'modified_user_class',
                'backend_testldap_user_name_attribute'  =>  'modified_user_name_attribute'
            )
        );

        $form->setResources(
            array(
                'db_resource'   =>  array(
                    'type' => 'db'
                ),
                'db_resource_2' =>  array(
                    'type' => 'db'
                )
            )
        );

        $form->setConfiguration($this->getTestConfig());
        $form->create();
        $view = new Zend_View();

        $html = new DOMDocument();
        $html->loadHTML($form->render($view));
        $this->assertEquals(
            null,
            $html->getElementById('backend_testldap_hostname-element'),
            'Asserting configuration to be hidden when an authentication is marked as to be removed'
        );
        $config = $form->getConfig();
        $this->assertFalse(
            isset($config['test-ldap']),
            'Asserting deleted backends not being persisted in the configuration'
        );

    }
}
