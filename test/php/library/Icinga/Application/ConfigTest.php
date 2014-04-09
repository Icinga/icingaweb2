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

namespace Tests\Icinga\Application;

use \Icinga\Application\Config as IcingaConfig;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set up config dir
     *
     * Utilizes singleton IcingaConfig
     *
     * @backupStaticAttributes  enabled
     */
    public function setUp()
    {
        IcingaConfig::$configDir = dirname(__FILE__) . '/Config/files';
    }

    public function testAppConfig()
    {
        $config = IcingaConfig::app();
        $this->assertEquals(1, $config->logging->enable, 'Unexpected value retrieved from config file');
        // Test non-existent property where null is the default value
        $this->assertEquals(
            null,
            $config->logging->get('disable'),
            'Unexpected default value for non-existent properties'
        );
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
