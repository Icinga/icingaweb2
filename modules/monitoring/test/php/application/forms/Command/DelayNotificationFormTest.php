<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Module\Monitoring\Application\Forms\Command;

use Icinga\Test\BaseTestCase;
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
