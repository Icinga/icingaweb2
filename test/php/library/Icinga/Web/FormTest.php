<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web;

use Mockery;
use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Test\BaseTestCase;

class PostRequest extends Request
{
    public function getMethod()
    {
        return 'POST';
    }
}

class SuccessfulForm extends Form
{
    public function onSuccess()
    {
        return true;
    }
}

class FormTest extends BaseTestCase
{
    public function tearDown()
    {
        Mockery::close(); // Necessary as some tests are running isolated
    }

    public function testWhetherASubmitButtonIsAddedWithASubmitLabelBeingSet()
    {
        $form = new Form();
        $form->setTokenDisabled();
        $form->setSubmitLabel('test');
        $form->create();

        $this->assertInstanceOf(
            '\Zend_Form_Element',
            $form->getElement('btn_submit'),
            'Form::create() does not add a submit button in case a submit label is set'
        );
    }

    public function testWhetherNoSubmitButtonIsAddedWithoutASubmitLabelBeingSet()
    {
        $form = new Form();
        $form->setTokenDisabled();
        $form->create();

        $this->assertNull(
            $form->getElement('btn_submit'),
            'Form::create() adds a submit button in case no submit label is set'
        );
    }

    /**
     * @depends testWhetherASubmitButtonIsAddedWithASubmitLabelBeingSet
     */
    public function testWhetherIsSubmittedReturnsTrueWithASubmitLabelBeingSet()
    {
        $form = new Form();
        $form->setTokenDisabled();
        $form->setSubmitLabel('test');
        $form->populate(array('btn_submit' => true));
        $form->setRequest(new PostRequest());

        $this->assertTrue(
            $form->isSubmitted(),
            'Form::isSubmitted() does not return true in case a submit label is set'
        );
    }

    /**
     * @depends testWhetherNoSubmitButtonIsAddedWithoutASubmitLabelBeingSet
     */
    public function testWhetherIsSubmittedReturnsFalseWithoutASubmitLabelBeingSet()
    {
        $form = new Form();

        $this->assertFalse(
            $form->isSubmitted(),
            'Form::isSubmitted() does not return false in case no submit label is set'
        );
    }

    public function testWhetherTheCurrentLocationIsUsedAsDefaultRedirection()
    {
        $this->getRequestMock()->shouldReceive('getPathInfo')->andReturn('default/route');
        $this->getResponseMock()->shouldReceive('redirectAndExit')->atLeast()->once()
            ->with(Mockery::on(function ($url) {
                return $url->getRelativeUrl() === 'default/route';
            }));

        $form = new SuccessfulForm();
        $form->setTokenDisabled();
        $form->setUidDisabled();
        $form->handleRequest();
    }

    public function testWhetherAnExplicitlySetRedirectUrlIsUsedForRedirection()
    {
        $this->getResponseMock()->shouldReceive('redirectAndExit')->atLeast()->once()
            ->with(Mockery::on(function ($url) {
                return $url->getRelativeUrl() === 'special/route';
            }));

        $form = new SuccessfulForm();
        $form->setTokenDisabled();
        $form->setUidDisabled();
        $form->setRedirectUrl('special/route');
        $form->handleRequest();
    }

    /**
     * @runInSeparateProcess
     */
    public function testWhetherACsrfCounterMeasureIsBeingAdded()
    {
        Mockery::mock('alias:Icinga\Web\Session')->shouldReceive('getSession->getId')->andReturn('1234567890');

        $form = new Form();
        $form->create();

        $this->assertInstanceOf(
            '\Zend_Form_Element',
            $form->getElement($form->getTokenElementName()),
            'Form::create() does not add a csrf counter measure element'
        );
    }

    public function testWhetherACsrfCounterMeasureIsNotBeingAdded()
    {
        $form = new Form();
        $form->setTokenDisabled();
        $form->create();

        $this->assertNull(
            $form->getElement($form->getTokenElementName()),
            'Form::create() adds a csrf counter measure element in case it\'s disabled'
        );
    }

    public function testWhetherAUniqueFormIdIsBeingAdded()
    {
        $form = new Form();
        $form->setTokenDisabled();
        $form->create();

        $this->assertInstanceOf(
            '\Zend_Form_Element',
            $form->getElement($form->getUidElementName()),
            'Form::create() does not add a form identification element'
        );
    }

    public function testWhetherAUniqueFormIdIsNotBeingAdded()
    {
        $form = new Form();
        $form->setTokenDisabled();
        $form->setUidDisabled();
        $form->create();

        $this->assertNull(
            $form->getElement($form->getUidElementName()),
            'Form::create() adds a form identification element in case it\'s disabled'
        );
    }

    /**
     * @depends testWhetherAUniqueFormIdIsBeingAdded
     */
    public function testWhetherAFormIsSentWithAUniqueFormIdBeingAdded()
    {
        $form = new Form();
        $form->setTokenDisabled();
        $form->create();

        $this->assertTrue(
            $form->wasSent(
                array(
                    $form->getUidElementName() => $form->getElement($form->getUidElementName())->getValue()
                )
            ),
            'Form::wasSent() does not return true in case a the form identification value is being sent'
        );
    }

    /**
     * @depends testWhetherAUniqueFormIdIsNotBeingAdded
     */
    public function testWhetherAFormIsNotSentWithoutAUniqueFormIdBeingAdded()
    {
        $form = new Form();
        $form->setTokenDisabled();
        $form->setUidDisabled();
        $form->create();

        $this->assertFalse(
            $form->wasSent(array()),
            'Form::wasSent() does not return false in case no form identification element was added'
        );
    }

    public function testWhetherADefaultActionIsBeingSetOnFormCreation()
    {
        $this->getRequestMock()->shouldReceive('getPathInfo')->andReturn('some/route');

        $form = new Form();
        $form->setTokenDisabled();
        $form->create();

        $this->assertEquals(
            '/some/route',
            $form->getAction(),
            'Form::create() does not set a default action if none was set explicitly'
        );
    }

    /**
     * @depends testWhetherAUniqueFormIdIsBeingAdded
     * @depends testWhetherASubmitButtonIsAddedWithASubmitLabelBeingSet
     */
    public function testWhetherItIsPossibleToRecreateAForm()
    {
        $form = new Form();
        $form->setTokenDisabled();
        $form->setSubmitLabel('test');
        $form->create(); // sets the flag $this->created to true
        $form->clearElements(); // should reset the flag..
        $form->create(); // ..so that we can recreate the form

        $this->assertCount(
            2,
            $form->getElements(),
            'Form::clearElements() does not fully reset the form'
        );
    }

    public function testWhetherGetNameReturnsTheEscapedClassNameByDefault()
    {
        $form = new Form();

        $this->assertEquals(
            $form->filterName(get_class($form)),
            $form->getName(),
            'Form::getName() does not return the escaped class name in case no name was explicitly set'
        );
    }

    /**
     * @expectedException \Icinga\Exception\ProgrammingError
     */
    public function testWhetherTheOnSuccessOptionMustBeCallable()
    {
        new Form(array('onSuccess' => '_invalid_'));
    }

    /**
     * @depends testWhetherACsrfCounterMeasureIsNotBeingAdded
     * @depends testWhetherAUniqueFormIdIsNotBeingAdded
     * @depends testWhetherNoSubmitButtonIsAddedWithoutASubmitLabelBeingSet
     */
    public function testWhetherAClosureCanBePassedAsOnSuccessCallback()
    {
        $request = new Request();
        $form = new Form(array(
            'onSuccess' => function ($form) {
                $form->getRequest()->setParam('test', 'tset');
                return false;
            }
        ));
        $form->setTokenDisabled();
        $form->setUidDisabled();
        $form->handleRequest($request);

        $this->assertEquals(
            'tset',
            $request->getParam('test'),
            'Form does not utilize the onSuccess callback set with form options on instantiation'
        );
    }
}
