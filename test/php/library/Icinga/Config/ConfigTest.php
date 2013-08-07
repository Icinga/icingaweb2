<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}


namespace Tests\Icinga\Application;

require_once 'Zend/Config/Ini.php';
require_once '../../library/Icinga/Config/Config.php';

use Icinga\Config\Config as IcingaConfig;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        IcingaConfig::$configDir = dirname(__FILE__) . '/files';
    }

    public function testAppConfig()
    {
        $config = IcingaConfig::app();
        $this->assertEquals(1, $config->logging->enable);
        // Test non-existent property where null is the default value
        $this->assertEquals(null, $config->logging->get('disable'));
        // Test non-existent property using zero as the default value
        $this->assertEquals(0, $config->logging->get('disable', 0));
        // Test retrieve full section
        $this->assertEquals(
            array(
                'disable' => 1,
                'db' => array(
                    'user' => 'user',
                    'password' => 'password'
                )
            ),
            $config->backend->toArray()
        );
        // Test non-existent section using 'default' as default value
        $this->assertEquals('default', $config->get('magic', 'default'));
        // Test sub-properties
        $this->assertEquals('user', $config->backend->db->user);
        // Test non-existent sub-property using 'UTF-8' as the default value
        $this->assertEquals('UTF-8', $config->backend->db->get('encoding', 'UTF-8'));
        // Test invalid property names using false as default value
        $this->assertEquals(false, $config->backend->get('.', false));
        $this->assertEquals(false, $config->backend->get('db.', false));
        $this->assertEquals(false, $config->backend->get('.user', false));
        // Test retrieve array of sub-properties
        $this->assertEquals(
            array(
                'user' => 'user',
                'password' => 'password'
            ),
            $config->backend->db->toArray()
        );
        // Test singleton
        $this->assertEquals($config, IcingaConfig::app());
        $this->assertEquals(array('logging', 'backend'), $config->keys());
        $this->assertEquals(array('enable'), $config->keys('logging'));
    }

    public function testAppExtraConfig()
    {
        $extraConfig = IcingaConfig::app('extra');
        $this->assertEquals(1, $extraConfig->meta->version);
        $this->assertEquals($extraConfig, IcingaConfig::app('extra'));
    }

    public function testModuleConfig()
    {
        $moduleConfig = IcingaConfig::module('amodule');
        $this->assertEquals(1, $moduleConfig->menu->get('breadcrumb'));
        $this->assertEquals($moduleConfig, IcingaConfig::module('amodule'));
    }

    public function testModuleExtraConfig()
    {
        $moduleExtraConfig = IcingaConfig::module('amodule', 'extra');
        $this->assertEquals(
            'inetOrgPerson',
            $moduleExtraConfig->ldap->user->get('ldap_object_class')
        );
        $this->assertEquals($moduleExtraConfig, IcingaConfig::module('amodule', 'extra'));
    }
}
