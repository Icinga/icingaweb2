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

namespace Test\Monitoring\Forms\Command;

require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Test/BaseTestCase.php');

use Icinga\Test\BaseTestCase;

require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/CommandForm.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/DelayNotificationForm.php';

use Icinga\Module\Monitoring\Form\Command\DelayNotificationForm;

class DelayNotificationFormTest extends BaseTestCase
{
    const FORM_CLASS = 'Icinga\Module\Monitoring\Form\Command\DelayNotificationForm';

    public function testFormInvalidWhenNotificationDelayMissing()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'minutes'       => '',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing notification delay must be considered invalid'
        );
    }

    public function testFormInvalidWhenNotificationDelayNaN()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'minutes'       => 'A String',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Incorrect notification delay, i.e. NaN must be considered invalid'
        );
    }

    public function testFormInvalidWhenNotificationDelayOverflows()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'minutes'       => DelayNotificationForm::MAX_DELAY + 1,
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Notification delay bigger than constant "DelayNotificationForm::MAX_DELAY" must be considered invalid'
        );
    }
}
