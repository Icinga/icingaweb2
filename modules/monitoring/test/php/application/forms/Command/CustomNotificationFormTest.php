<?php

namespace Test\Monitoring\Forms\Command;

require_once __DIR__. '/BaseFormTest.php';
require_once __DIR__. '/../../../../../application/forms/Command/ConfirmationForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/CustomNotificationForm.php';


use Monitoring\Form\Command\CustomNotificationForm;
use \Zend_View;
use \Zend_Test_PHPUnit_ControllerTestCase;

class CustomNotificationFormTest extends BaseFormTest
{
    public function testForm1()
    {
        $form = $this->getRequestForm(array(
            'comment'    => 'TEST COMMENT',
            'author'     => 'LAOLA',
            'btn_submit' => 'foo'
        ), "Monitoring\Form\Command\CustomNotificationForm");
        $form->buildForm();

        $this->assertCount(7, $form->getElements());
        $this->assertTrue($form->isSubmittedAndValid());
    }
}

