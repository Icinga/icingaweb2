<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Test/BaseTestCase.php');

use Icinga\Test\BaseTestCase;

require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/CommandForm.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/CustomNotificationForm.php';

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
