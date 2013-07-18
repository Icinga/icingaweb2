<?php

namespace {
    if (!function_exists('t')) {
        function t() {
            return func_get_arg(0);
        }
    }

    if (!function_exists('mt')) {
        function mt() {
            return func_get_arg(0);
        }
    }
}

namespace Test\Monitoring\Forms\Command {

    require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';
    require_once 'Zend/Form.php';
    require_once 'Zend/View.php';
    require_once 'Zend/Form/Element/Submit.php';
    require_once 'Zend/Form/Element/Reset.php';
    require_once 'Zend/Form/Element/Checkbox.php';
    require_once 'Zend/Validate/Date.php';

    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form.php';
    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form/Element/Note.php';
    require_once __DIR__. '/../../../../../../../library/Icinga/Web/Form/Element/DateTime.php';
    require_once __DIR__. '/../../../../../application/forms/Command/AbstractCommand.php';
    require_once __DIR__. '/../../../../../application/forms/Command/WithChildrenCommand.php';
    require_once __DIR__. '/../../../../../application/forms/Command/ScheduleDowntime.php';

    use Monitoring\Form\Command\ScheduleDowntime;
    use \Zend_View;
    use \Zend_Test_PHPUnit_ControllerTestCase;

    class ScheduleDowntimeTest extends Zend_Test_PHPUnit_ControllerTestCase
    {
        public function testFormElements1()
        {
            $this->getRequest()->setPost(
                array(

                )
            );

            $form = new ScheduleDowntime();
            $form->setRequest($this->getRequest());
            $form->buildForm();

            $this->assertCount(13, $form->getElements());

            $form = new ScheduleDowntime();
            $form->setRequest($this->getRequest());
            $form->setWithChildren(true);
            $form->buildForm();

            $this->assertCount(12, $form->getElements());
        }


        public function testFormValidation1()
        {
            $this->getRequest()->setPost(
                array(

                )
            );

            $form = new ScheduleDowntime();
            $form->setRequest($this->getRequest());
            $form->setWithChildren(true);

            $this->assertTrue(
                $form->isValid(
                    array(
                        'author'    => 'TEST_AUTHOR',
                        'comment'   => 'DING DING',
                        'triggered' => '4',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 10:30:00',
                        'type'      => ScheduleDowntime::TYPE_FIXED,
                        'hours'     => '',
                        'minutes'   => '',
                        // 'childobjects' => '',
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'    => 'TEST_AUTHOR',
                        'comment'   => 'DING DING',
                        'triggered' => '4',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 10:30:00',
                        'type'      => ScheduleDowntime::TYPE_FLEXIBLE,
                        'hours'     => '',
                        'minutes'   => '',
                        // 'childobjects' => '',
                    )
                )
            );

            $this->assertTrue(
                $form->isValid(
                    array(
                        'author'    => 'TEST_AUTHOR',
                        'comment'   => 'DING DING',
                        'triggered' => '4',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 10:30:00',
                        'type'      => ScheduleDowntime::TYPE_FLEXIBLE,
                        'hours'     => '10',
                        'minutes'   => '10',
                        // 'childobjects' => '',
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'    => '',
                        'comment'   => 'DING DING',
                        'triggered' => '4',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 10:30:00',
                        'type'      => ScheduleDowntime::TYPE_FIXED,
                        'hours'     => '',
                        'minutes'   => '',
                        // 'childobjects' => '',
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'    => 'OK',
                        'comment'   => '',
                        'triggered' => '4',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 10:30:00',
                        'type'      => ScheduleDowntime::TYPE_FIXED,
                        'hours'     => '',
                        'minutes'   => '',
                        // 'childobjects' => '',
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'    => 'OK',
                        'comment'   => 'OK',
                        'triggered' => 'HAHA',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 10:30:00',
                        'type'      => ScheduleDowntime::TYPE_FIXED,
                        'hours'     => '',
                        'minutes'   => '',
                        // 'childobjects' => '',
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'    => 'OK',
                        'comment'   => 'OK',
                        'triggered' => '123',
                        'starttime' => '2013-07-17',
                        'endtime'   => '2013-07-17 10:30:00',
                        'type'      => ScheduleDowntime::TYPE_FIXED,
                        'hours'     => '',
                        'minutes'   => '',
                        // 'childobjects' => '',
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'    => 'OK',
                        'comment'   => 'OK',
                        'triggered' => '123',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => 'DING',
                        'type'      => ScheduleDowntime::TYPE_FIXED,
                        'hours'     => '',
                        'minutes'   => '',
                        // 'childobjects' => '',
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'    => 'OK',
                        'comment'   => 'OK',
                        'triggered' => '123',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 09:30:00',
                        'type'      => ScheduleDowntime::TYPE_FLEXIBLE,
                        'hours'     => '-1',
                        'minutes'   => '12',
                        // 'childobjects' => '',
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'    => 'OK',
                        'comment'   => 'OK',
                        'triggered' => '123',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 09:30:00',
                        'type'      => ScheduleDowntime::TYPE_FLEXIBLE,
                        'hours'     => '12',
                        'minutes'   => 'DING',
                        // 'childobjects' => '',
                    )
                )
            );

        }

        public function testFormValidation2()
        {
            $this->getRequest()->setPost(
                array(

                )
            );

            $form = new ScheduleDowntime();
            $form->setWithChildren(false);
            $form->setRequest($this->getRequest());

            $this->assertTrue(
                $form->isValid(
                    array(
                        'author'    => 'TEST_AUTHOR',
                        'comment'   => 'DING DING',
                        'triggered' => '4',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 10:30:00',
                        'type'      => ScheduleDowntime::TYPE_FIXED,
                        'hours'     => '',
                        'minutes'   => '',
                        'childobjects' => '0',
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'    => 'TEST_AUTHOR',
                        'comment'   => 'DING DING',
                        'triggered' => '4',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 10:30:00',
                        'type'      => ScheduleDowntime::TYPE_FIXED,
                        'hours'     => '',
                        'minutes'   => '',
                        'childobjects' => 'AHA',
                    )
                )
            );

            $this->assertFalse(
                $form->isValid(
                    array(
                        'author'    => 'TEST_AUTHOR',
                        'comment'   => 'DING DING',
                        'triggered' => '4',
                        'starttime' => '2013-07-17 10:30:00',
                        'endtime'   => '2013-07-17 10:30:00',
                        'type'      => ScheduleDowntime::TYPE_FIXED,
                        'hours'     => '',
                        'minutes'   => '',
                        'childobjects' => '4',
                    )
                )
            );
        }

        public function testTimeRange()
        {
            $this->getRequest()->setPost(
                array(

                )
            );

            $form = new ScheduleDowntime();
            $form->setWithChildren(false);
            $form->setRequest($this->getRequest());
            $form->buildForm();

            $time1 = strtotime($form->getElement('starttime')->getValue());
            $time2 = strtotime($form->getElement('endtime')->getValue());

            $this->assertEquals(3600, ($time2 - $time1));
        }
    }
}
