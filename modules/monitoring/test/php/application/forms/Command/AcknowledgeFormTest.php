<?php



namespace Test\Monitoring\Forms\Command;

require_once __DIR__.'/BaseFormTest.php';
$base = __DIR__.'/../../../../../../../';
require_once $base.'modules/monitoring/application/forms/Command/ConfirmationForm.php';
require_once realpath($base.'modules/monitoring/application/forms/Command/WithChildrenCommandForm.php');
require_once realpath($base.'modules/monitoring/application/forms/Command/AcknowledgeForm.php');

use Monitoring\Form\Command\AcknowledgeForm;
use \Zend_View;
use \Zend_Test_PHPUnit_ControllerTestCase;

class AcknowledgeFormTest extends BaseFormTest
{
    const FORMCLASS = "Monitoring\Form\Command\AcknowledgeForm";

    public function testForm()
    {
        $formWithoutExpiration = $this->getRequestForm(array(), self::FORMCLASS);
        $formWithoutExpiration->buildForm();
        $formWithExpiration = $this->getRequestForm(array(
            'expire' => '1'
        ), self::FORMCLASS);
        $formWithExpiration->buildForm();

        $this->assertCount(10, $formWithoutExpiration->getElements());
        $this->assertCount(11, $formWithExpiration->getElements());
    }

    public function testValidateCorrectForm()
    {
        $form = $this->getRequestForm(array(
            'author'     => 'test1',
            'comment'    => 'test comment',
            'persistent' => '0',
            'expire'     => '0',
            'sticky'     => '0',
            'notify'     => '0',
            'btn_submit' => 'foo'
        ), self::FORMCLASS);

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            "Asserting a correct form to be validated correctly"
        );
    }

    public function testDetectMissingAcknowledgementComment()
    {
        $form = $this->getRequestForm(array(
            'author'     => 'test1',
            'comment'    => '',
            'persistent' => '0',
            'expire'     => '0',
            'sticky'     => '0',
            'notify'     => '0',
            'btn_submit' => 'foo'
        ), self::FORMCLASS);
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            "Asserting a missing comment text to cause validation errors"
        );
    }

    public function testValidateMissingExpireTime()
    {
        $form = $this->getRequestForm(array(
            'author'     => 'test1',
            'comment'    => 'test comment',
            'persistent' => '0',
            'expire'     => '1',
            'expiretime' => '',
            'sticky'     => '0',
            'notify'     => '0',
            'btn_submit' => 'foo'
        ), self::FORMCLASS);
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            "Asserting a missing expire time to cause validation errors when expire is 1"
        );
    }

    public function testValidateIncorrectExpireTime()
    {
        $form = $this->getRequestForm(array(
            'author'     => 'test1',
            'comment'    => 'test comment',
            'persistent' => '0',
            'expire'     => '1',
            'expiretime' => 'NOT A DATE',
            'sticky'     => '0',
            'notify'     => '0',
            'btn_submit' => 'foo'
        ), self::FORMCLASS);
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            "Assert incorrect dates to be recognized when validating expiretime"
        );
    }

    public function testValidateCorrectAcknowledgementWithExpireTime()
    {
        $form = $this->getRequestForm(array(
            'author'     => 'test1',
            'comment'    => 'test comment',
            'persistent' => '0',
            'expire'     => '1',
            'expiretime' => '2013-07-10 17:32:16',
            'sticky'     => '0',
            'notify'     => '0',
            'btn_submit' => 'foo'
        ), self::FORMCLASS);
        $this->assertTrue(
            $form->isSubmittedAndValid(),
            "Assert that correct expire time acknowledgement is considered valid"
        );
    }
}

