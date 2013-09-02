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


namespace Tests\Icinga\Regression;

use \Icinga\Test\BaseTestCase;
use \Icinga\Application\Logger;
use \Zend_Config;

require_once 'Zend/Log.php';
require_once 'Zend/Config.php';
require_once 'Zend/Log/Writer/Mock.php';
require_once 'Zend/Log/Writer/Null.php';
require_once 'Zend/Log/Filter/Priority.php';

require_once realpath(__DIR__ . '/../../../library/Icinga/Test/BaseTestCase.php');
require_once realpath(BaseTestCase::$libDir.'/Application/Logger.php');

/**
 * Bug 4595 : "If log disabled, default target (./var/log) is not writable / no path exist"
 *
 * This is caused because the logger ignored the 'enable' parameter
 */
class Regression4595 extends BaseTestCase {

    public function testDisableLogging()
    {
        $cfg = new Zend_Config(
            array(
                'enable'     => '0',
                'type'      => 'mock',
                'target'    => 'target2'
            )
        );
        $logger = new Logger($cfg);
        $writers = $logger->getWriters();
        $this->assertEquals(0, count($writers), 'Assert that loggers aren\'t registered when "enable" is set to false');
    }
}