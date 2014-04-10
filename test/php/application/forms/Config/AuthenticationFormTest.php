<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config;

use \Zend_Config;
use Icinga\Web\Url;
use Icinga\Test\BaseTestCase;
use Tests\Icinga\Web\RequestMock;

/**
 * Test for the authentication provider form
 */
class AuthenticationFormTest extends BaseTestCase
{
    /**
     * Test the ldap provider form population from config
     */
    public function testLdapProvider()
    {
        $form = $this->createForm('Icinga\Form\Config\Authentication\LdapBackendForm');
        $config = new Zend_Config(
            array(
                'backend'               => 'ldap',
                'target'                => 'user',
                'user_class'            => 'testClass',
                'user_name_attribute'   => 'testAttribute'
            )
        );
        $form->setBackendName('testldap');
        $form->setBackend($config);
        $form->create(array('resources' => array()));

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
        $this->markTestSkipped('ReorderForm is broken');
        Url::$overwrittenRequest = new RequestMock();
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
        $this->markTestSkipped('ReorderForm is broken');
        Url::$overwrittenRequest = new RequestMock();
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
