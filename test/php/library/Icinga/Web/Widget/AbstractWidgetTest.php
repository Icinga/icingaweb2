<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mhein
 * Date: 6/10/13
 * Time: 1:31 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Tests\Icinga\Web\Widget;

require_once '../../library/Icinga/Exception/ProgrammingError.php';
require_once '../../library/Icinga/Web/Widget.php';
require_once '../../library/Icinga/Web/Widget/AbstractWidget.php';

require_once 'Zend/View.php';
require_once 'Zend/Controller/Action/HelperBroker.php';

use Icinga\Web\Widget\AbstractWidget;

/**
 * Class TestWidget
 * @package Tests\Icinga\Web\Widget
 * @property boolean $test1
 * @property boolean $test2
 */
class TestWidget extends AbstractWidget
{
    protected $properties = array(
        'test1' => true,
        'test2' => false
    );

    public function renderAsHtml()
    {
        return "ok123123";
    }
}

class TestWidget2 extends AbstractWidget
{
    protected function init()
    {
        $this->view();
    }

    public function getView()
    {
        return $this->view();
    }

    public function renderAsHtml()
    {
        return "ok123123";
    }

}

class AbstractWidgetTest extends \PHPUnit_Framework_TestCase
{
    public function testAbstractImplementation1()
    {
        $widget = new TestWidget(
            array(
                'test1' => false,
                'test2' => true
            )
        );

        $this->assertTrue($widget->test2);
        $this->assertFalse($widget->test1);

        $this->assertEquals('ok123123', (string)$widget);
        $this->assertEquals('ok123123', $widget->renderAsHtml());
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage Trying to set invalid "test3" property in Tests\Icinga\Web\Widget\TestWidget. Allowed are: test1, test2
     */
    public function testSetFail1()
    {
        $widget = new TestWidget();
        $widget->test3 = true;
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage Trying to set invalid "unknown" property in Tests\Icinga\Web\Widget\TestWidget2. Allowed are: none
     */
    public function testSetFail2()
    {
        $widget = new TestWidget2();
        $widget->unknown = true;
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage Trying to get invalid "test3" property for Tests\Icinga\Web\Widget\TestWidget
     */
    public function testGetFail()
    {
        $widget = new TestWidget();
        $target = $widget->test3;
    }

    public function testView1()
    {
        $widget = new TestWidget2();
        $view = $widget->getView();
        $this->assertInstanceOf('Zend_View', $view);
    }
}