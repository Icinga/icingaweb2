<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Module\Monitoring\Application\Forms\Command;

use Icinga\Test\BaseTestCase;

class AcknowledgeFormTest extends BaseTestCase
{
    const FORM_CLASS = 'Icinga\Module\Monitoring\Form\Command\AcknowledgeForm';

    public function testFormValid()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => 'Comment',
                'persistent'    => '0',
                'expire'        => '0',
                'sticky'        => '0',
                'notify'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Legal request data without expire time must be considered valid'
        );

        $formWithExpireTime = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => 'Comment',
                'persistent'    => '0',
                'expire'        => '1',
                'expiretime'    => '10/07/2013 5:32 PM',
                'sticky'        => '0',
                'notify'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertTrue(
            $formWithExpireTime->isSubmittedAndValid(),
            'Legal request data with expire time must be considered valid'
        );
    }

    public function testFormInvalidWhenCommentMissing()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => '',
                'persistent'    => '0',
                'expire'        => '0',
                'sticky'        => '0',
                'notify'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing comment must be considered not valid'
        );
    }

    public function testFormInvalidWhenExpireTimeMissingAndExpireSet()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => 'Comment',
                'persistent'    => '0',
                'expire'        => '1',
                'sticky'        => '0',
                'notify'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'If expire is set and expire time is missing, the form must not be valid'
        );
    }

    public function testFormInvalidWhenExpireTimeIsIncorrectAndExpireSet()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'        => 'Author',
                'comment'       => 'Comment',
                'persistent'    => '0',
                'expire'        => '1',
                'expiretime'    => 'Not a date',
                'sticky'        => '0',
                'notify'        => '0',
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'If expire is set and expire time is incorrect, the form must not be valid'
        );
    }
}
