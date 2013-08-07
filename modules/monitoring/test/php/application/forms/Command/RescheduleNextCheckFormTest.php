<?php



namespace Test\Monitoring\Forms\Command;


require_once __DIR__. '/BaseFormTest.php';
require_once __DIR__. '/../../../../../application/forms/Command/CommandForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/WithChildrenCommandForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/RescheduleNextCheckForm.php';


use Monitoring\Form\Command\RescheduleNextCheckForm;
use \Zend_View;
use \Zend_Test_PHPUnit_ControllerTestCase;

class RescheduleNextCheckFormTest extends BaseFormTest
{

    const FORMCLASS = 'Monitoring\Form\Command\RescheduleNextCheckForm';

    public function testValidRescheduleSubmissions()
    {

        $form = $this->getRequestForm(array(
            'checktime'  => '2013-10-19 17:30:00',
            'forcecheck' => 1,
            'btn_submit' => 'foo'
        ), self::FORMCLASS);
        $form->buildForm();

        $this->assertCount(6, $form->getElements());

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Asserting a reschedule form with correct time and forececheck=1 to be valid'
        );
        $form = $this->getRequestForm(array(
            'checktime'  => '2013-10-19 17:30:00',
            'forcecheck' => 0,
            'btn_submit' => 'foo'
        ), self::FORMCLASS);

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            'Asserting a reschedule form with correct time and forecheck=0 to be valid'
        );
    }

    public function testInValidRescheduleChecktimeSubmissions()
    {
        $form = $this->getRequestForm(array(
            'checktime'  => '2013-24-12 17:30:00',
            'forcecheck' => 1
        ), self::FORMCLASS);

        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Asserting an logically invalid checktime to be considered as invalid reschedule data'
        );

        $form = $this->getRequestForm(array(
            'checktime'  => 'AHAHA',
            'forcecheck' => 1
        ), self::FORMCLASS);


        $this->assertFalse(
            $form->isSubmittedAndValid(),
            'Asserting an invalid non-numeric checktime to be considered as invalid reschedule data'
        );
    }

    public function testChildrenFlag()
    {

        $form = new RescheduleNextCheckForm();
        $form->setRequest($this->getRequest());
        $form->setWithChildren(true);
        $form->buildForm();
        $notes1 = $form->getNotes();
        $form = null;

        $form = new RescheduleNextCheckForm();
        $form->setRequest($this->getRequest());
        $form->setWithChildren(false);
        $form->buildForm();
        $notes2 = $form->getNotes();
        $form = null;

        $form = new RescheduleNextCheckForm();
        $form->setRequest($this->getRequest());
        $form->setWithChildren();
        $form->buildForm();
        $notes3 = $form->getNotes();
        $form = null;

        $this->assertEquals($notes1, $notes3);
        $this->assertNotEquals($notes1, $notes2);
    }
}
