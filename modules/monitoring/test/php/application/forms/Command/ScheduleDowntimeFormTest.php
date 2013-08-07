<?php

namespace Test\Monitoring\Forms\Command;

require_once __DIR__. '/BaseFormTest.php';
require_once __DIR__. '/../../../../../application/forms/Command/CommandForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/WithChildrenCommandForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/ScheduleDowntimeForm.php';

use Monitoring\Form\Command\ScheduleDowntimeForm;
use \Zend_View;
use \Zend_Test_PHPUnit_ControllerTestCase;

class ScheduleDowntimeFormTest extends BaseFormTest
{
    const FORMCLASS = 'Monitoring\Form\Command\ScheduleDowntimeForm';
    public function testCorrectFormElementCreation()
    {
        $formFixed = $this->getRequestForm(array(), self::FORMCLASS);
        $formFixed->buildForm();
        $formFlexible = $this->getRequestForm(array(
            'type' => 'flexible'
        ), self::FORMCLASS);
        $formFlexible->buildForm();

        $this->assertCount(11, $formFixed->getElements());
        $this->assertCount(13, $formFlexible->getElements());

        $form = $this->getRequestForm(array(), self::FORMCLASS);
        $form->setWithChildren(true);
        $form->buildForm();

        $this->assertCount(12, $form->getElements());
    }


    public function testCorrectValidationWithChildrend()
    {
        $form = $this->getRequestForm(array(
            'author'     => 'TEST_AUTHOR',
            'comment'    => 'DING DING',
            'triggered'  => '4',
            'starttime'  => '2013-07-17 10:30:00',
            'endtime'    => '2013-07-17 10:30:00',
            'type'       => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'      => '',
            'minutes'    => '',
            'btn_submit' => 'foo',
            // 'childobjects' => '',
        ), self::FORMCLASS);


        $form->setWithChildren(true);

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Asserting a correct fixed downtime form to be considered valid'
        );
        $form = $this->getRequestForm(array(
            'author'     => 'TEST_AUTHOR',
            'comment'    => 'DING DING',
            'triggered'  => '4',
            'starttime'  => '2013-07-17 10:30:00',
            'endtime'    => '2013-07-17 10:30:00',
            'type'       => ScheduleDowntimeForm::TYPE_FLEXIBLE,
            'hours'      => '10',
            'minutes'    => '10',
            'btn_submit' => 'foo'
            // 'childobjects' => '',
        ), self::FORMCLASS);
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
            'starttime' => '2013-07-17 10:30:00',
            'endtime'   => '2013-07-17 10:30:00',
            'type'      => ScheduleDowntimeForm::TYPE_FLEXIBLE,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORMCLASS);
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
            'starttime' => '2013-07-17 10:30:00',
            'endtime'   => '2013-07-17 10:30:00',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORMCLASS);
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
            'starttime' => '2013-07-17 10:30:00',
            'endtime'   => '2013-07-17 10:30:00',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORMCLASS);
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
            'starttime' => '2013-07-17 10:30:00',
            'endtime'   => '2013-07-17 10:30:00',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORMCLASS);
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
            'starttime' => '2013-07-17',
            'endtime'   => '2013-07-17 10:30:00',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORMCLASS);
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
            'starttime' => '2013-07-17 10:30:00',
            'endtime'   => 'DING',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            // 'childobjects' => '',
        ), self::FORMCLASS);
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
            'starttime' => '2013-07-17 10:30:00',
            'endtime'   => '2013-07-17 09:30:00',
            'type'      => ScheduleDowntimeForm::TYPE_FLEXIBLE,
            'hours'     => '-1',
            'minutes'   => '12',
            // 'childobjects' => '',
        ), self::FORMCLASS);
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
            'starttime' => '2013-07-17 10:30:00',
            'endtime'   => '2013-07-17 09:30:00',
            'type'      => ScheduleDowntimeForm::TYPE_FLEXIBLE,
            'hours'     => '12',
            'minutes'   => 'DING',
            // 'childobjects' => '',
        ), self::FORMCLASS);
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
            'starttime'    => '2013-07-17 10:30:00',
            'endtime'      => '2013-07-17 10:30:00',
            'type'         => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'        => '',
            'minutes'      => '',
            'btn_submit'   => 'foo',
            'childobjects' => '0',
        ), self::FORMCLASS);
        $form->setWithChildren(false);


        $this->assertTrue(
            $form->isSubmittedAndValid(),
            "Assert a correct schedule downtime without children form to be considered valid"
        );
    }

    public function testIncorrectChildObjectsRecognition() {
        $form = $this->getRequestForm(array(
            'author'    => 'TEST_AUTHOR',
            'comment'   => 'DING DING',
            'triggered' => '4',
            'starttime' => '2013-07-17 10:30:00',
            'endtime'   => '2013-07-17 10:30:00',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            'childobjects' => 'AHA',
        ), self::FORMCLASS);
        $form->setWithChildren(false);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            "Assert and incorrect (non-numeric) childobjects value to cause validation errors"
        );

        $form = $this->getRequestForm(array(
            'author'    => 'TEST_AUTHOR',
            'comment'   => 'DING DING',
            'triggered' => '4',
            'starttime' => '2013-07-17 10:30:00',
            'endtime'   => '2013-07-17 10:30:00',
            'type'      => ScheduleDowntimeForm::TYPE_FIXED,
            'hours'     => '',
            'minutes'   => '',
            'childobjects' => '4',
        ), self::FORMCLASS);
        $form->setWithChildren(false);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            "Assert and incorrect (numeric) childobjects value to cause validation errors"
        );
    }

    public function testTimeRange()
    {
        $form = $this->getRequestForm(array(), self::FORMCLASS);
        $form->buildForm();

        $time1 = strtotime($form->getElement('starttime')->getValue());
        $time2 = strtotime($form->getElement('endtime')->getValue());

        $this->assertEquals(3600, ($time2 - $time1));
    }
}
