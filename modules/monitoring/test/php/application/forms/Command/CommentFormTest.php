<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath('library/Icinga/Web/Form/BaseFormTest.php');
require_once realpath(__DIR__ . '/../../../../../../../modules/monitoring/application/forms/Command/CommentForm.php');

use \Icinga\Module\Monitoring\Form\Command\CommentForm; // Used by constant FORMCLASS
use \Test\Icinga\Web\Form\BaseFormTest;

class CommentFormTest extends BaseFormTest
{
    const FORMCLASS = 'Monitoring\Form\Command\CommentForm';

    public function testCorrectCommentValidation()
    {
        $form = $this->getRequestForm(array(
            'author'        => 'Author',
            'comment'       => 'Comment',
            'sticky'        => '0',
            'btn_submit'    => 'Submit'
        ), self::FORMCLASS);

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Legal request data must be considered valid'
        );
    }

    public function testFormInvalidWhenCommentMissing()
    {
        $form = $this->getRequestForm(array(
            'author'        => 'Author',
            'comment'       => '',
            'sticky'        => '0',
            'btn_submit'    => 'Submit'

        ), self::FORMCLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing comment must be considered not valid'
        );
    }

    public function testFormInvalidWhenAuthorMissing()
    {
        $form = $this->getRequestForm(array(
            'author'        => '',
            'comment'       => 'Comment',
            'sticky'        => '0',
            'btn_submit'    => 'Submit'
        ), self::FORMCLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing author must be considered not valid'
        );
    }
}
// @codingStandardsIgnoreEnd
