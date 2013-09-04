<?php
// {{{ICINGA_LICENSE_HEADER}}}
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
