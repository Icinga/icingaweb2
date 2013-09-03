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

require_once BaseTestCase::$testDir . '/library/Icinga/Web/RequestMock.php';

require_once BaseTestCase::$libDir . '/Web/Form.php';
require_once BaseTestCase::$libDir . '/Web/Url.php';

require_once BaseTestCase::$appDir . '/forms/Config/Authentication/BaseBackendForm.php';
require_once BaseTestCase::$appDir . '/forms/Config/Authentication/DbBackendForm.php';
require_once BaseTestCase::$appDir . '/forms/Config/Authentication/LdapBackendForm.php';
require_once BaseTestCase::$appDir . '/forms/Config/Authentication/ReorderForm.php';
// @codingStandardsIgnoreEnd

use \Zend_Config;
use \Icinga\Web\Url;
use \Tests\Icinga\Web\RequestMock;

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
                    'backend'               => 'ldap',
                    'target'                => 'user',
                    'hostname'              => 'test host',
                    'root_dn'               => 'ou=test,dc=icinga,dc=org',
                    'bind_dn'               => 'cn=testuser,cn=config',
                    'bind_pw'               => 'password',
                    'user_class'            => 'testClass',
                    'user_name_attribute'   => 'testAttribute'
                )
            )
        );
    }

    /**
     * Test the ldap provider form population from config
     */
    public function testLdapProvider()
    {
        $this->requireFormLibraries();
        $form = $this->createForm('Icinga\Form\Config\Authentication\LdapBackendForm');
        $config = new Zend_Config(
            array(
                'backend'               => 'ldap',
                'target'                => 'user',
                'hostname'              => 'test host',
                'root_dn'               => 'ou=test,dc=icinga,dc=org',
                'bind_dn'               => 'cn=testuser,cn=config',
                'bind_pw'               => 'password',
                'user_class'            => 'testClass',
                'user_name_attribute'   => 'testAttribute'
            )
        );
        $form->setBackendName('testldap');
        $form->setBackend($config);
        $form->create();

        // parameters to be hidden
        $notShown = array('backend', 'target');
        foreach ($config->toArray() as $name => $value) {
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
     */
    public function testDbProvider()
    {
        $this->requireFormLibraries();
        $form = $this->createForm('Icinga\Form\Config\Authentication\DbBackendForm');
        $config = new Zend_Config(
            array(
                'backend'   =>  'db',
                'target'    =>  'user',
                'resource'  =>  'db_resource'
            )
        );
        $form->setResources(
            array(
                'db_resource' => array(
                    'type'      => 'db'
                )
            )
        );

        $form->setBackendName('test-db');
        $form->setBackend($config);
        $form->create();

        // parameters to be hidden
        $notShown = array('backend', 'target');
        foreach ($config->toArray() as $name => $value) {
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
     * @backupStaticAttributes enabled
     */
    public function testModifyOrder()
    {
        Url::$overwrittenRequest = new RequestMock();
        $this->requireFormLibraries();
        $form = $this->createForm('Icinga\Form\Config\Authentication\ReorderForm');
        $form->setAuthenticationBackend('backend2');
        $form->setCurrentOrder(array('backend1', 'backend2', 'backend3', 'backend4'));

        $form->create();
        $this->assertSame(
            2,
            count($form->getSubForms()),
            'Assert that a form for moving backend up and down exists'
        );
        $this->assertTrue(
            $form->upForm->getElement('form_backend_order') !== null,
            'Assert that a "move backend up" button exists'
        );
        $this->assertSame(
            array('backend2', 'backend1', 'backend3', 'backend4'),
            explode(',', $form->upForm->getElement('form_backend_order')->getValue()),
            'Assert the "move backend up" button containing the correct order'
        );

        $this->assertTrue(
            $form->downForm->getElement('form_backend_order') !== null,
            'Assert that a "move backend down" button exists'
        );
        $this->assertSame(
            array('backend1', 'backend3', 'backend2', 'backend4'),
            explode(',', $form->downForm->getElement('form_backend_order')->getValue()),
            'Assert the "move backend up" button containing the correct order'
        );
    }

    /**
     * Test whether the reorder form doesn't display senseless ordering (like moving the uppermost element up or
     * the lowermose down)
     *
     * @backupStaticAttributes enabled
     */
    public function testInvalidOrderingNotShown()
    {
        Url::$overwrittenRequest = new RequestMock();
        $this->requireFormLibraries();
        $form = $this->createForm('Icinga\Form\Config\Authentication\ReorderForm');
        $form->setAuthenticationBackend('backend1');
        $form->setCurrentOrder(array('backend1', 'backend2', 'backend3', 'backend4'));

        $form->create();
        $this->assertSame(
            2,
            count($form->getSubForms()),
            'Assert that a form for moving backend up and down exists, even when moving up is not possible'
        );
        $this->assertTrue(
            $form->downForm->getElement('form_backend_order') !== null,
            'Assert that a "move backend down" button exists when moving up is not possible'
        );
        $this->assertTrue(
            $form->upForm->getElement('form_backend_order') === null,
            'Assert that a "move backend up" button does not exist when moving up is not possible'
        );
    }
}
