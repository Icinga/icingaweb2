<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Module\Monitoring\Application\Forms\Command;

use \DateTimeZone;
use Icinga\Test\BaseTestCase;
use Icinga\Util\DateTimeFactory;
use Icinga\Module\Monitoring\Form\Command\ScheduleDowntimeForm;

class ScheduleDowntimeFormTest extends BaseTestCase
{
    const FORM_CLASS = 'Icinga\Module\Monitoring\Form\Command\ScheduleDowntimeForm';

    /**
     * Set up the default time zone
     *
     * Utilizes singleton DateTimeFactory
     *
     * @backupStaticAttributes enabled
     */
    public function setUp()
    {
        DateTimeFactory::setConfig(array('timezone' => new DateTimeZone('UTC')));
    }

    public function testCorrectFormElementCreation()
    {
        $formFixed = $this->createForm(self::FORM_CLASS);
        $formFixed->setCurrentDowntimes(array('foo'));
        $formFixed->buildForm();
        $formFlexible = $this->createForm(
            self::FORM_CLASS,
            array(
                'type' => 'flexible'
            )
        );
        $formFlexible->setCurrentDowntimes(array('foo'));
        $formFlexible->buildForm();

        $form = $this->createForm(self::FORM_CLASS);
        $form->setCurrentDowntimes(array('foo'));
        $form->setWithChildren(true);
        $form->buildForm();
    }

    public function testCorrectValidationWithChildrend()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'     => 'TEST_AUTHOR',
                'comment'    => 'DING DING',
                'triggered'  => '0',
                'starttime'  => '17/07/2013 10:30 AM',
                'endtime'    => '18/07/2013 10:30 AM',
                'type'       => ScheduleDowntimeForm::TYPE_FIXED,
                'hours'      => '',
                'minutes'    => '',
                'btn_submit' => 'foo',
            )
        );
        $form->setWithChildren(true);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Asserting a correct fixed downtime form to be considered valid'
        );
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'     => 'TEST_AUTHOR',
                'comment'    => 'DING DING',
                'triggered'  => '0',
                'starttime'  => '17/07/2013 10:30 AM',
                'endtime'    => '18/07/2013 10:30 AM',
                'type'       => ScheduleDowntimeForm::TYPE_FLEXIBLE,
                'hours'      => '10',
                'minutes'    => '10',
                'btn_submit' => 'foo'
            )
        );
        $form->setWithChildren(true);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Asserting a correct flexible downtime form to be considered valid'
        );
    }

    public function testMissingFlexibleDurationRecognition()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'    => 'TEST_AUTHOR',
                'comment'   => 'DING DING',
                'triggered' => '0',
                'starttime' => '17/07/2013 10:30 AM',
                'endtime'   => '18/07/2013 10:30 AM',
                'type'      => ScheduleDowntimeForm::TYPE_FLEXIBLE,
                'hours'     => '',
                'minutes'   => '',
            )
        );
        $form->setWithChildren(true);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert missing hours and minutes in downtime form to cause failing validation'
        );
    }

    public function testMissingAuthorRecognition()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'    => '',
                'comment'   => 'DING DING',
                'triggered' => '0',
                'starttime' => '17/07/2013 10:30 AM',
                'endtime'   => '18/07/2013 10:30 AM',
                'type'      => ScheduleDowntimeForm::TYPE_FIXED,
                'hours'     => '',
                'minutes'   => '',
            )
        );
        $form->setWithChildren(true);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert missing author to cause validation errors in fixed downtime'
        );
    }

    public function testMissingCommentRecognition()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'    => 'OK',
                'comment'   => '',
                'triggered' => '0',
                'starttime' => '17/07/2013 10:30 AM',
                'endtime'   => '18/07/2013 10:30 AM',
                'type'      => ScheduleDowntimeForm::TYPE_FIXED,
                'hours'     => '',
                'minutes'   => '',
            )
        );
        $form->setWithChildren(true);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert missing comment to cause validation errors in fixed downtime'
        );
    }

    public function testInvalidTriggeredFieldValueRecognition()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'    => 'OK',
                'comment'   => 'OK',
                'triggered' => 'HAHA',
                'starttime' => '17/07/2013 10:30 AM',
                'endtime'   => '18/07/2013 10:30 AM',
                'type'      => ScheduleDowntimeForm::TYPE_FIXED,
                'hours'     => '',
                'minutes'   => '',
            )
        );
        $form->setWithChildren(true);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert invalid trigger field to cause validation to fail'
        );
    }

    public function testInvalidStartTimeRecognition()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'    => 'OK',
                'comment'   => 'OK',
                'triggered' => '0',
                'starttime' => '17/07/2013',
                'endtime'   => '18/07/2013 10:30 AM',
                'type'      => ScheduleDowntimeForm::TYPE_FIXED,
                'hours'     => '',
                'minutes'   => '',
            )
        );
        $form->setWithChildren(true);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert incorrect start time to cause validation errors in fixed downtime'
        );
    }

    public function testInvalidEndTimeRecognition()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'    => 'OK',
                'comment'   => 'OK',
                'triggered' => '0',
                'starttime' => '17/07/2013 10:30 AM',
                'endtime'   => 'DING',
                'type'      => ScheduleDowntimeForm::TYPE_FIXED,
                'hours'     => '',
                'minutes'   => '',
            )
        );
        $form->setWithChildren(true);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert invalid endtime to cause validation errors in fixed downtime'
        );
    }

    public function testInvalidHoursValueRecognitionInFlexibleDowntime()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'    => 'OK',
                'comment'   => 'OK',
                'triggered' => '0',
                'starttime' => '17/07/2013 10:30 AM',
                'endtime'   => '18/07/2013 10:30 AM',
                'type'      => ScheduleDowntimeForm::TYPE_FLEXIBLE,
                'hours'     => '-1',
                'minutes'   => '12',
            )
        );
        $form->setWithChildren(true);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert negative hours to cause validation errors in flexible downtime'
        );
    }

    public function testInvalidMinutesValueRecognitionInFlexibleDowntime()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'    => 'OK',
                'comment'   => 'OK',
                'triggered' => '0',
                'starttime' => '17/07/2013 10:30 AM',
                'endtime'   => '18/07/2013 10:30 AM',
                'type'      => ScheduleDowntimeForm::TYPE_FLEXIBLE,
                'hours'     => '12',
                'minutes'   => 'DING',
            )
        );
        $form->setWithChildren(true);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert non numeric valud to cause validation errors in flexible downtime '
        );

    }

    public function testCorrectScheduleDowntimeWithoutChildrenForm()
    {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'       => 'TEST_AUTHOR',
                'comment'      => 'DING DING',
                'triggered'    => '0',
                'starttime'    => '17/07/2013 10:30 AM',
                'endtime'      => '18/07/2013 10:30 AM',
                'type'         => ScheduleDowntimeForm::TYPE_FIXED,
                'hours'        => '',
                'minutes'      => '',
                'btn_submit'   => 'foo',
                'childobjects' => '0',
            )
        );
        $form->setWithChildren(false);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Assert a correct schedule downtime without children form to be considered valid'
        );
    }

    public function testIncorrectChildObjectsRecognition() {
        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'       => 'TEST_AUTHOR',
                'comment'      => 'DING DING',
                'triggered'    => '0',
                'starttime'    => '17/07/2013 10:30 AM',
                'endtime'      => '18/07/2013 10:30 AM',
                'type'         => ScheduleDowntimeForm::TYPE_FIXED,
                'hours'        => '',
                'minutes'      => '',
                'childobjects' => 'AHA',
            )
        );
        $form->setWithChildren(false);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert and incorrect (non-numeric) childobjects value to cause validation errors'
        );

        $form = $this->createForm(
            self::FORM_CLASS,
            array(
                'author'       => 'TEST_AUTHOR',
                'comment'      => 'DING DING',
                'triggered'    => '0',
                'starttime'    => '17/07/2013 10:30 AM',
                'endtime'      => '18/07/2013 10:30 AM',
                'type'         => ScheduleDowntimeForm::TYPE_FIXED,
                'hours'        => '',
                'minutes'      => '',
                'childobjects' => '4',
            )
        );
        $form->setWithChildren(false);
        $form->setCurrentDowntimes(array('foo'));

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Assert and incorrect (numeric) childobjects value to cause validation errors'
        );
    }

    public function testTimeRange()
    {
        $form = $this->createForm(self::FORM_CLASS);
        $form->setCurrentDowntimes(array('foo'));
        $form->buildForm();

        $time1 = $form->getElement('starttime')->getValue();
        $time2 = $form->getElement('endtime')->getValue();

        $this->assertEquals(3600, ($time2 - $time1));
    }
}
