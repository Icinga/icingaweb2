<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Form\Config\Authentication;

use Mockery;
use Zend_Config;
use Icinga\Test\BaseTestCase;
use Icinga\Form\Config\Authentication\ReorderForm;

class RequestLessReorderForm extends ReorderForm
{
    public $order;

    public function getValues($suppressArrayNotation = false)
    {
        return array('form_backend_order' => $this->order);
    }
}

class ReorderFormTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->viewMock = Mockery::mock('\Zend_View');
        $this->viewMock->shouldReceive('icon')->andReturn('');
    }

    public function testMoveBackendUp()
    {
        $config = new Zend_Config(
            array(
                'test1' => '',
                'test2' => '',
                'test3' => ''
            )
        );
        $oldOrder = array_keys($config->toArray());

        $form = new RequestLessReorderForm();
        $form->setCurrentOrder($oldOrder);
        $form->setBackendName('test3');
        $form->setView($this->viewMock);
        $form->create();

        $form->order = $form->getSubForm('btn_reorder_up')->getElement('form_backend_order')->getValue();
        $this->assertSame(
            $form->getReorderedConfig($config),
            array('test1' => '', 'test3' => '', 'test2' => ''),
            'Moving elements up with ReorderForm does not seem to properly work'
        );
    }

    public function testMoveBackendDown()
    {
        $config = new Zend_Config(
            array(
                'test1' => '',
                'test2' => '',
                'test3' => ''
            )
        );
        $oldOrder = array_keys($config->toArray());

        $form = new RequestLessReorderForm();
        $form->setCurrentOrder($oldOrder);
        $form->setBackendName('test1');
        $form->setView($this->viewMock);
        $form->create();

        $form->order = $form->getSubForm('btn_reorder_down')->getElement('form_backend_order')->getValue();
        $this->assertSame(
            $form->getReorderedConfig($config),
            array('test2' => '', 'test1' => '', 'test3' => ''),
            'Moving elements down with ReorderForm does not seem to properly work'
        );
    }
}
