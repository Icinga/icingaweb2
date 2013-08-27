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

namespace Test\Icinga\Form\Preference;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
// @codingStandardsIgnoreEnd

use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once 'Zend/Config.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Form/Element/Select.php';
require_once 'Zend/View/Helper/Abstract.php';
require_once BaseTestCase::$libDir . '/User/Preferences.php';
require_once BaseTestCase::$libDir . '/Web/Form.php';
require_once BaseTestCase::$appDir . '/forms/Preference/GeneralForm.php';
require_once BaseTestCase::$libDir . '/User/Preferences/ChangeSet.php';
require_once BaseTestCase::$appDir . '/views/helpers/DateFormat.php';
require_once BaseTestCase::$libDir . '/Util/ConfigAwareFactory.php';
require_once BaseTestCase::$libDir . '/Util/DateTimeFactory.php';
// @codingStandardsIgnoreEnd

use \DateTimeZone;
use \Zend_Config;
use \Icinga\User\Preferences;
use \Zend_View_Helper_DateFormat;
use \Icinga\Util\DateTimeFactory;

/**
 * Test for general form, mainly testing enable/disable behaviour
 */
class GeneralFormTest extends BaseTestCase
{
    /**
     * Test whether fields using the default values have input disabled
     *
     */
    public function testDisableFormIfUsingDefault()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
        $this->requireFormLibraries();
        $form = $this->createForm('Icinga\Form\Preference\GeneralForm');
        $form->setDateFormatter(new Zend_View_Helper_DateFormat($this->getRequest()));
        $form->setRequest($this->getRequest());
        $form->setConfiguration(
            new Zend_Config(
                array(
                    'timezone' => 'UTC'
                )
            )
        );
        $form->setUserPreferences(
            new Preferences(
                array()
            )
        );
        $form->create();
        $this->assertSame(
            1,
            $form->getElement('timezone')->getAttrib('disabled'),
            'Asserting form elements to be disabled when not set in a preference'
        );
    }

    /**
     *  Test whether fields with preferences are enabled
     *
     */
    public function testEnableFormIfUsingPreference()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
        $this->requireFormLibraries();
        $form = $this->createForm('Icinga\Form\Preference\GeneralForm');
        $form->setDateFormatter(new Zend_View_Helper_DateFormat($this->getRequest()));
        $form->setRequest($this->getRequest());
        $form->setConfiguration(
            new Zend_Config(
                array(
                    'timezone' => 'UTC'
                )
            )
        );
        $form->setUserPreferences(
            new Preferences(
                array(
                    'app.timezone' => 'Europe/Berlin'
                )
            )
        );
        $form->create();
        $this->assertSame(
            null,
            $form->getElement('timezone')->getAttrib('disabled'),
            'Asserting form elements to be disabled when not set in a preference'
        );
    }
}
