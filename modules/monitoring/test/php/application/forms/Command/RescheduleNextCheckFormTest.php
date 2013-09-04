<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Test/BaseTestCase.php');

use Icinga\Test\BaseTestCase;

require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/CommandForm.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/WithChildrenCommandForm.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/forms/Command/RescheduleNextCheckForm.php';
require_once BaseTestCase::$libDir .  '/Util/ConfigAwareFactory.php';
require_once BaseTestCase::$libDir .  '/Util/DateTimeFactory.php';

use \DateTimeZone;
use Icinga\Util\DateTimeFactory;

class RescheduleNextCheckFormTest extends BaseTestCase
{
    const FORM_CLASS = 'Icinga\Module\Monitoring\Form\Command\RescheduleNextCheckForm';

    /**
     * Set DateTimeFactory's time zone to UTC
     *
     * Utilizes singleton DateTimeFactory
     *
     * @backupStaticAttributes enabled
     */
    public function setUp()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
    }

    public function testFormInvalidWhenChecktimeIsIncorrect()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'checktime'     => '2013-24-12 17:30:00',
                'forcecheck'    => 0,
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Asserting a logically incorrect checktime as invalid'
        );

        $form2 = $this->createForm(
            self::FORM_CLASS,
            array(
                'checktime'     => 'Captain Morgan',
                'forcecheck'    => 1,
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form2->isSubmittedAndValid(),
            'Providing arbitrary strings as checktime must be considered invalid'
        );

        $form3 = $this->createForm(
            self::FORM_CLASS,
            array(
                'checktime'     => '',
                'forcecheck'    => 0,
                'btn_submit'    => 'Submit'
            )
        );
        $this->assertFalse(
            $form3->isSubmittedAndValid(),
            'Missing checktime must be considered invalid'
        );
    }
}
