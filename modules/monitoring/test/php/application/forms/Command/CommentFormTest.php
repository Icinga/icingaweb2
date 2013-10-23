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
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/CommentForm.php';

class CommentFormTest extends BaseTestCase
{
    const FORM_CLASS = 'Icinga\Module\Monitoring\Form\Command\CommentForm';

    public function testCorrectCommentValidation()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => 'Comment',
                'sticky'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Legal request data must be considered valid'
        );
    }

    public function testFormInvalidWhenCommentMissing()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => '',
                'sticky'        => '0',
                'btn_submit'    => 'Submit'

            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing comment must be considered not valid'
        );
    }

    public function testFormInvalidWhenAuthorMissing()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => '',
                'comment'       => 'Comment',
                'sticky'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing author must be considered not valid'
        );
    }
}
