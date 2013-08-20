<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Forms\Command;

require_once realpath('library/Icinga/Web/Form/BaseFormTest.php');
require_once realpath(__DIR__ . '/../../../../../../../modules/monitoring/application/forms/Command/ScheduleDowntimeForm.php');
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Util/ConfigAwareFactory.php');
require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Util/DateTimeFactory.php');

use \Icinga\Module\Monitoring\Form\Command\ScheduleDowntimeForm; // Used by constant FORM_CLASS
use \DateTimeZone;
use \Icinga\Util\DateTimeFactory;
use \Test\Icinga\Web\Form\BaseFormTest;

class ScheduleDowntimeFormTest extends BaseFormTest
{
    const FORM_CLASS = '\Icinga\Module\Monitoring\Form\Command\ScheduleDowntimeForm';

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

    public function testCorrectFormElementCreation()
    {
        $formFixed = $this->getRequestForm(array(), self::FORM_CLASS);
        $formFixed->buildForm();
        $formFlexible = $this->getRequestForm(array(
            'type' => 'flexible'
        ), self::FORM_CLASS);
        $formFlexible->buildForm();

        $form = $this->getRequestForm(array(), self::FORM_CLASS);
        $form->setWithChildren(true);
        $form->buildForm();
    }

    public function testCorrectValidationWithChildrend()
    {
        $form = $this->getRequestForm(array(
            'author'     => 'TEST_AUTHOR',
            'comment'    => 'DING DING',
            'triggered'  => '4',
            'starttime'  => '17/07/2013 10:30 AM',
            'endtime'    => '18/07/2013 10:30 AM',
            'type'       => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'      => '',
            'minutes'    => '',
            'btn_submit' => 'foo',
            // 'childobjects' => '',
        ), self::FORM_CLASS);

        $form->setWithChildren(true);

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Asserting a correct fixed downtime form to be considered valid'
        );
        $form = $this->getRequestForm(array(
            'author'     => 'TEST_AUTHOR',
            'comment'    => 'DING DING',
            'triggered'  => '4',
            'starttime'  => '17/07/2013 10:30 AM',
            'endtime'    => '18/07/2013 10:30 AM',
            'type'       => ScheduleDowntimeForm::TYPE_FLEXIBLE,
            'hours'      => '10',
            'minutes'    => '10',
            'btn_submit' => 'foo'
            // 'childobjects' => '',
        ), self::FORM_CLASS);
        $form->setWithChildren(true);

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Asserting a correct flexible downtime form to be considered valid'
        );
    }

    public function testMissingFlexibleDurationRecognition()
    {
        $form = $this->getRequestForm(array(
            'author'    => 'TEST_AUTHOR',
            'comment'   => 'DING DING',
            'triggered' => '4',
            'starttime' => '17/07/2013 10:30 AM',
            'endtime'   => '18/07/2013 10:30 AM',
            'type'      => ScheduleDowntimeForm::TYPE_FLEXIBLE,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORM_CLASS);
        $form->setWithChildren(true);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert missing hours and minutes in downtime form to cause failing validation'
        );
    }

    public function testMissingAuthorRecognition()
    {
        $form = $this->getRequestForm(array(
            'author'    => '',
            'comment'   => 'DING DING',
            'triggered' => '4',
            'starttime' => '17/07/2013 10:30 AM',
            'endtime'   => '18/07/2013 10:30 AM',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORM_CLASS);
        $form->setWithChildren(true);


        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert missing author to cause validation errors in fixed downtime'
        );
    }

    public function testMissingCommentRecognition()
    {
        $form = $this->getRequestForm(array(
            'author'    => 'OK',
            'comment'   => '',
            'triggered' => '4',
            'starttime' => '17/07/2013 10:30 AM',
            'endtime'   => '18/07/2013 10:30 AM',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORM_CLASS);
        $form->setWithChildren(true);


        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert missing comment to cause validation errors in fixed downtime'
        );
    }

    public function testInvalidTriggeredFieldValueRecognition()
    {
        $form = $this->getRequestForm(array(
            'author'    => 'OK',
            'comment'   => 'OK',
            'triggered' => 'HAHA',
            'starttime' => '17/07/2013 10:30 AM',
            'endtime'   => '18/07/2013 10:30 AM',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORM_CLASS);
        $form->setWithChildren(true);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert invalid trigger field to cause validation to fail'
        );
    }

    public function testInvalidStartTimeRecognition()
    {
        $form = $this->getRequestForm(array(
            'author'    => 'OK',
            'comment'   => 'OK',
            'triggered' => '123',
            'starttime' => '17/07/2013',
            'endtime'   => '18/07/2013 10:30 AM',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORM_CLASS);
        $form->setWithChildren(true);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert incorrect start time to cause validation errors in fixed downtime'
        );
    }

    public function testInvalidEndTimeRecognition()
    {
        $form = $this->getRequestForm(array(
            'author'    => 'OK',
            'comment'   => 'OK',
            'triggered' => '123',
            'starttime' => '17/07/2013 10:30 AM',
            'endtime'   => 'DING',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORM_CLASS);
        $form->setWithChildren(true);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert invalid endtime to cause validation errors in fixed downtime'
        );
    }


    public function testInvalidHoursValueRecognitionInFlexibleDowntime()
    {
        $form = $this->getRequestForm(array(
            'author'    => 'OK',
            'comment'   => 'OK',
            'triggered' => '123',
            'starttime' => '17/07/2013 10:30 AM',
            'endtime'   => '18/07/2013 10:30 AM',
            'type'      => ScheduleDowntimeForm::TYPE_FLEXIBLE,
            'hours'     => '-1',
            'minutes'   => '12',
            // 'childobjects' => '',
        ), self::FORM_CLASS);
        $form->setWithChildren(true);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert negative hours to cause validation errors in flexible downtime'
        );
    }

    public function testInvalidMinutesValueRecognitionInFlexibleDowntime()
    {
        $form = $this->getRequestForm(array(
            'author'    => 'OK',
            'comment'   => 'OK',
            'triggered' => '123',
            'starttime' => '17/07/2013 10:30 AM',
            'endtime'   => '18/07/2013 10:30 AM',
            'type'      => ScheduleDowntimeForm::TYPE_FLEXIBLE,
            'hours'     => '12',
            'minutes'   => 'DING',
            // 'childobjects' => '',
        ), self::FORM_CLASS);
        $form->setWithChildren(true);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert non numeric valud to cause validation errors in flexible downtime '
        );

    }

    public function testCorrectScheduleDowntimeWithoutChildrenForm()
    {
        $form = $this->getRequestForm(array(
            'author'       => 'TEST_AUTHOR',
            'comment'      => 'DING DING',
            'triggered'    => '4',
            'starttime'    => '17/07/2013 10:30 AM',
            'endtime'      => '18/07/2013 10:30 AM',
            'type'         => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'        => '',
            'minutes'      => '',
            'btn_submit'   => 'foo',
            'childobjects' => '0',
        ), self::FORM_CLASS);
        $form->setWithChildren(false);


        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Assert a correct schedule downtime without children form to be considered valid'
        );
    }

    public function testIncorrectChildObjectsRecognition() {
        $form = $this->getRequestForm(array(
            'author'    => 'TEST_AUTHOR',
            'comment'   => 'DING DING',
            'triggered' => '4',
            'starttime' => '17/07/2013 10:30 AM',
            'endtime'   => '18/07/2013 10:30 AM',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            'childobjects' => 'AHA',
        ), self::FORM_CLASS);
        $form->setWithChildren(false);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert and incorrect (non-numeric) childobjects value to cause validation errors'
        );

        $form = $this->getRequestForm(array(
            'author'    => 'TEST_AUTHOR',
            'comment'   => 'DING DING',
            'triggered' => '4',
            'starttime' => '17/07/2013 10:30 AM',
            'endtime'   => '18/07/2013 10:30 AM',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            'childobjects' => '4',
        ), self::FORM_CLASS);
        $form->setWithChildren(false);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert and incorrect (numeric) childobjects value to cause validation errors'
        );
    }

    public function testTimeRange()
    {
        $form = $this->getRequestForm(array(), self::FORM_CLASS);
        $form->buildForm();

        $time1 = $form->getElement('starttime')->getValue();
        $time2 = $form->getElement('endtime')->getValue();

        $this->assertEquals(3600, ($time2 - $time1));
    }
}
// @codingStandardsIgnoreStop
