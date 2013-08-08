<?php

namespace Tests\Icinga\Web\Hook\Configuration;

require_once '../../library/Icinga/Web/Hook/Configuration/ConfigurationTabInterface.php';
require_once '../../library/Icinga/Web/Hook/Configuration/ConfigurationTab.php';
require_once '../../library/Icinga/Web/Hook/Configuration/ConfigurationTabBuilder.php';
require_once '../../library/Icinga/Web/Hook.php';
require_once '../../library/Icinga/Web/Widget/Widget.php';
require_once '../../library/Icinga/Web/Widget/Tabs.php';
require_once '../../library/Icinga/Web/Widget/Tab.php';
require_once '../../library/Icinga/Exception/ProgrammingError.php';

use Icinga\Web\Hook\Configuration\ConfigurationTab;
use Icinga\Web\Hook;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabs;
use PHPUnit_Framework_TestResult;

class RequestMock
{
    public function getBaseUrl()
    {
        return "/";
    }

}

class ConfigurationTabBuilderTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        Hook::clean();
        Url::$overwrittenRequest = new RequestMock();
    }

    protected function tearDown()
    {
        parent::tearDown();
        Hook::clean();
        Url::$overwrittenRequest = null;
    }

    public function testDefaultTabs()
    {
        $widget = new Tabs();
        $builder = new Hook\Configuration\ConfigurationTabBuilder($widget);

        $array = $builder->build();
        $tabs = $builder->getTabs();

        $this->assertInstanceOf('Icinga\\Web\\Widget\\Tab', $tabs->get('configuration'));
    }

    public function testTabCreation1()
    {
        $widget = new Tabs();
        $builder = new Hook\Configuration\ConfigurationTabBuilder($widget);

        $tab1 = new ConfigurationTab('test1', '/test1', 'TEST1');
        $tab2 = new ConfigurationTab('test2', '/test2', 'TEST2');
        $tab3 = new ConfigurationTab('test3', '/test3', 'TEST3');

        Hook::registerObject(Hook\Configuration\ConfigurationTabBuilder::HOOK_NAMESPACE, 'test1', $tab1);
        Hook::registerObject(Hook\Configuration\ConfigurationTabBuilder::HOOK_NAMESPACE, 'test2', $tab2);
        Hook::registerObject(Hook\Configuration\ConfigurationTabBuilder::HOOK_NAMESPACE, 'test3', $tab3);

        $builder->build();

        $this->assertCount(5, $builder->getTabs());
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage tab not instance of ConfigTabInterface
     */
    public function testTabCreation2()
    {
        $widget = new Tabs();
        $builder = new Hook\Configuration\ConfigurationTabBuilder($widget);

        $tab = new \stdClass();
        Hook::registerObject(Hook\Configuration\ConfigurationTabBuilder::HOOK_NAMESPACE, 'misc', $tab);
        $builder->build();

        $this->assertCount(5, $builder->getTabs());
    }
}