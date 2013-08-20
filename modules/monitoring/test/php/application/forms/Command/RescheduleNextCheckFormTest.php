<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath('library/Icinga/Web/Form/BaseFormTest.php');
require_once realpath(__DIR__ . '/../../../../../application/forms/Command/RescheduleNextCheckForm.php');
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Util/ConfigAwareFactory.php');
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Util/DateTimeFactory.php');

use \Icinga\Module\Monitoring\Form\Command\RescheduleNextCheckForm; // Used by constant FORM_CLASS
use \DateTimeZone;
use \Icinga\Util\DateTimeFactory;
use \Test\Icinga\Web\Form\BaseFormTest;

class RescheduleNextCheckFormTest extends BaseFormTest
{
    const FORM_CLASS = 'Monitoring\Form\Command\RescheduleNextCheckForm';

    /**
     * Set up the default time zone
     *
     * Utilizes singleton DateTimeFactory
     *
     * @backupStaticAttributes enabled
     */
    public function setUp()
    {
        date_default_timezone_set('UTC');
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
    }

    public function testFormInvalidWhenChecktimeIsIncorrect()
    {
        $form = $this->getRequestForm(array(
            'checktime'     => '2013-24-12 17:30:00',
            'forcecheck'    => 0,
            'btn_submit'    => 'Submit'
        ), self::FORM_CLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Asserting a logically incorrect checktime as invalid'
        );

        $form2 = $this->getRequestForm(array(
            'checktime'     => 'Captain Morgan',
            'forcecheck'    => 1,
            'btn_submit'    => 'Submit'
        ), self::FORM_CLASS);

        $this->assertFalse(
            $form2->isSubmittedAndValid(),
            'Providing arbitrary strings as checktime must be considered invalid'
        );

        $form3 = $this->getRequestForm(array(
            'checktime'     => '',
            'forcecheck'    => 0,
            'btn_submit'    => 'Submit'
        ), self::FORM_CLASS);

        $this->assertFalse(
            $form3->isSubmittedAndValid(),
            'Missing checktime must be considered invalid'
        );
    }
}
// @codingStandardsIgnoreStop
