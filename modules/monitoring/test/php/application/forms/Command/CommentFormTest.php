<?php

namespace Test\Monitoring\Forms\Command;


require_once __DIR__.'/BaseFormTest.php';
require_once __DIR__. '/../../../../../application/forms/Command/CommandForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/WithChildrenCommandForm.php';
require_once __DIR__. '/../../../../../application/forms/Command/CommentForm.php';

use Monitoring\Form\Command\CommentForm;
use \Zend_View;


class CommentFormTest extends BaseFormTest
{
    const FORMCLASS = "Monitoring\Form\Command\CommentForm";
    public function testForm()
    {
        $form = new CommentForm();
        $form->setRequest($this->getRequest());
        $form->buildForm();

        $this->assertCount(6, $form->getElements());
    }


    public function testCorrectCommentValidation()
    {
        $form = $this->getRequestForm(array(
            'author'     => 'test1',
            'comment'    => 'test2',
            'sticky'     => '0',
            'btn_submit' => 'foo'
        ), self::FORMCLASS);

        $this->assertTrue(
            $form->isSubmittedAndValid(),
            "Asserting correct comment form to be considered valid"
        );
    }

    public function testRecognizeMissingCommentText()
    {
        $form = $this->getRequestForm(array(
            'author'  => 'test1',
            'comment' => '',
            'sticky'  => '0'
        ), self::FORMCLASS);
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            "Asserting missing comment text in comment form to cause validation errors"
        );
    }

    public function testRecognizeMissingCommentAuthor()
    {
        $form = $this->getRequestForm(array(
            'author'  => '',
            'comment' => 'test2',
            'sticky'  => '0'
        ), self::FORMCLASS);
        $this->assertFalse(
            $form->isSubmittedAndValid(),
            "Asserting missing comment author to cause validation errors"
        );
    }
}
