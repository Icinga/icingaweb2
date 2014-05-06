<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Module\Monitoring\Application\Forms\Command;

use Icinga\Test\BaseTestCase;

class CustomNotificationFormTest extends BaseTestCase
{
    const FORM_CLASS = 'Icinga\Module\Monitoring\Form\Command\CustomNotificationForm';

    public function testFormInvalidWhenCommentMissing()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => '',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing comment must be considered not valid'
        );
    }
}
