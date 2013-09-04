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

namespace Test\Icinga\Web\Form\Validator;

require_once('Zend/Validate/Abstract.php');
require_once(realpath('../../library/Icinga/Web/Form/Validator/WritablePathValidator.php'));
require_once(realpath('../../library/Icinga/Application/Config.php'));

use \PHPUnit_Framework_TestCase;
use \Icinga\Web\Form\Validator\WritablePathValidator;

class WritablePathValidatorTest extends PHPUnit_Framework_TestCase
{

    public function testValidateInputWithWritablePath()
    {
        $validator = new WritablePathValidator();
        if (!is_writeable('/tmp')) {
            $this->markTestSkipped('Need /tmp to be writable for testing WritablePathValidator');
        }
        $this->assertTrue(
            $validator->isValid(
                '/tmp/test',
                'Asserting a writable path to result in correct validation'
            )
        );
    }

    public function testValidateInputWithNonWritablePath()
    {
        $validator = new WritablePathValidator();
        $this->assertFalse(
            $validator->isValid(
                '/etc/shadow',
                'Asserting a non writable path to result in a validation error'
            )
        );
    }
}