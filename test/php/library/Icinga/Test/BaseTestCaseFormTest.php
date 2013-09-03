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

namespace Tests\Icinga\Test;

require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');

use \Icinga\Test\BaseTestCase;

require_once BaseTestCase::$appDir . '/forms/Authentication/LoginForm.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Config/ConfirmRemovalForm.php';

class BaseTestCaseFormTest extends BaseTestCase
{
    public function testFormCreation()
    {
        $form1 = $this->createForm('Icinga\Form\Authentication\LoginForm');
        $this->assertInstanceOf('Icinga\Web\Form', $form1);
        $form2 = $this->createForm('Monitoring\Form\Config\ConfirmRemovalForm');
        $this->assertInstanceOf('Icinga\Web\Form', $form2);
    }
}
