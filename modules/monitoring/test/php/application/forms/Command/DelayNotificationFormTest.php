<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath('library/Icinga/Web/Form/BaseFormTest.php');
require_once realpath(__DIR__ . '/../../../../../application/forms/Command/DelayNotificationForm.php');

use \Test\Icinga\Web\Form\BaseFormTest;
use \Icinga\Module\Monitoring\Form\Command\DelayNotificationForm; // Used by constant FORM_CLASS

class DelayNotificationFormTest extends BaseFormTest
{
    const FORM_CLASS = '\Icinga\Module\Monitoring\Form\Command\DelayNotificationForm';

    public function testFormInvalidWhenNotificationDelayMissing()
    {
        $form = $this->getRequestForm(array(
            'minutes'       => '',
            'btn_submit'    => 'Submit'
        ), self::FORM_CLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Missing notification delay must be considered invalid'
        );
    }

    public function testFormInvalidWhenNotificationDelayNaN()
    {
        $form = $this->getRequestForm(array(
            'minutes'       => 'A String',
            'btn_submit'    => 'Submit'
        ), self::FORM_CLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Incorrect notification delay, i.e. NaN must be considered invalid'
        );
    }

    public function testFormInvalidWhenNotificationDelayOverflows()
    {
        $form = $this->getRequestForm(array(
            'minutes'       => DelayNotificationForm::MAX_DELAY + 1,
            'btn_submit'    => 'Submit'
        ), self::FORM_CLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Notification delay bigger than constant "DelayNotificationForm::MAX_DELAY" must be considered invalid'
        );
    }
}
// @codingStandardsIgnoreEnd
