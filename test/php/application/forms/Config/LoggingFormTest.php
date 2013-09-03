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
require_once BaseTestCase::$appDir . '/forms/Config/GeneralForm.php';
require_once BaseTestCase::$appDir . '/forms/Config/LoggingForm.php';
// @codingStandardsIgnoreEnd

use \Zend_Config;

/**
 * Test for the authentication provider form
 *
 */
class LoggingFormTest extends BaseTestCase
{
    /**
     * Test the logging form to be correctly populated from configuration
     *
     */
    public function testLoggingFormPopulation()
    {
        $this->requireFormLibraries();
        $form = $this->createForm('Icinga\Form\Config\LoggingForm');
        $config = new Zend_Config(
            array(
                'logging' => array(
                    'enable'    => 1,
                    'target'    => '/some/path',
                    'verbose'   => 0,
                    'type'      => 'stream',
                    'debug'     => array(
                        'enable'    => 1,
                        'target'    => '/some/debug/path',
                        'type'      => 'stream'
                    )
                )
            )
        );
        $form->setConfiguration($config);
        $form->setBaseDir('basedir');
        $form->create();

        $this->assertEquals(
            '0',
            $form->getValue('logging_app_verbose'),
            'Asserting the logging verbose tick not to be set'
        );
        $this->assertEquals(
            '/some/path',
            $form->getValue('logging_app_target'),
            'Asserting the logging path to be set'
        );
        $this->assertEquals(
            1,
            $form->getValue('logging_debug_enable'),
            'Asserting the debug log enable tick to be set'
        );
        $this->assertEquals(
            '/some/debug/path',
            $form->getValue('logging_debug_target'),
            'Asserting the debug log path to be set'
        );
    }

    /**
     * Test the logging form to create correct modified configurations when submit
     *
     */
    public function testCorrectConfigCreation()
    {
        $this->requireFormLibraries();
        $form = $this->createForm(
            'Icinga\Form\Config\LoggingForm',
            array(
                'logging_enable'        => 1,
                'logging_app_target'    => 'some/new/target',
                'logging_app_verbose'   => 1,
                'logging_debug_enable'  => 0,
                'logging_debug_target'  => 'a/new/target'
            )
        );
        $baseConfig = new Zend_Config(
            array(
                'global' => array(
                    'option' => 'value'
                ),
                'logging' => array(
                    'enable'    => 1,
                    'target'    => '/some/path',
                    'verbose'   => 0,
                    'type'      => 'stream',
                    'debug'     => array(
                        'enable'    => 1,
                        'target'    => '/some/debug/path',
                        'type'      => 'stream'
                    )
                )
            )
        );
        $form->setConfiguration($baseConfig);
        $form->setBaseDir('basedir');
        $form->create();
        $form->populate($this->getRequest()->getParams());
        $config = $form->getConfig();
        $this->assertEquals(
            'value',
            $config->global->option,
            'Asserting global options not to be altered when changing log'
        );
        $this->assertEquals(
            1,
            $config->logging->enable,
            'Asserting logging to stay enabled when enable is ticked'
        );
        $this->assertEquals(
            'some/new/target',
            $config->logging->target,
            'Asserting target modifications to be applied'
        );
        $this->assertEquals(
            1,
            $config->logging->verbose,
            'Asserting ticking the verbose checkbox to be applied'
        );
        $this->assertEquals(
            'stream',
            $config->logging->type,
            'Asserting the type to stay "stream"'
        );
        $this->assertEquals(
            0,
            $config->logging->debug->enable,
            'Asserting debug log to be disabled'
        );
        $this->assertEquals(
            'a/new/target',
            $config->logging->debug->target,
            'Asserting the debug log target modifications to be applied'
        );
    }
}
