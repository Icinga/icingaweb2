<?php

namespace Tests\Icinga\Web;

use Icinga\Web\Widget;

require_once '../../library/Icinga/Web/Widget.php';
require_once '../../library/Icinga/Web/Widget/AbstractWidget.php';
require_once '../../library/Icinga/Web/Widget/Tab.php';
require_once '../../library/Icinga/Exception/ProgrammingError.php';

class WidgetTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate1()
    {
        $widgetCreator = new Widget();
        $widget = $widgetCreator->create('tab', array('name' => 'TEST'));

        $this->assertInstanceOf('Icinga\Web\Widget\Tab', $widget);
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage There is no such widget: DOES_NOT_EXIST
     */
    public function testFail1()
    {
        $widgetCreator = new Widget();
        $widget = $widgetCreator->create('DOES_NOT_EXIST');
    }
}