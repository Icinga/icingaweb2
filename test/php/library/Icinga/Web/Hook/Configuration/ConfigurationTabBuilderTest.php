<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web\Hook\Configuration;

use \Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Hook\Configuration\ConfigurationTab;
use Icinga\Web\Hook\Configuration\ConfigurationTabBuilder;
use Icinga\Web\Hook;
use Icinga\Web\Widget\Tabs;

class ConfigurationTabBuilderTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        Hook::clean();
    }

    public function tearDown()
    {
        parent::tearDown();
        Hook::clean();
    }

    public function testDefaultTabs()
    {
        $widget = new Tabs();
        $builder = new ConfigurationTabBuilder($widget);

        $array = $builder->build();
        $tabs = $builder->getTabs();

        $this->assertInstanceOf('Icinga\\Web\\Widget\\Tab', $tabs->get('configuration'));
    }

    public function testTabCreation1()
    {
        $widget = new Tabs();
        $builder = new ConfigurationTabBuilder($widget);

        $tab1 = new ConfigurationTab('test1', '/test1', 'TEST1');
        $tab2 = new ConfigurationTab('test2', '/test2', 'TEST2');
        $tab3 = new ConfigurationTab('test3', '/test3', 'TEST3');

        Hook::registerObject(ConfigurationTabBuilder::HOOK_NAMESPACE, 'test1', $tab1);
        Hook::registerObject(ConfigurationTabBuilder::HOOK_NAMESPACE, 'test2', $tab2);
        Hook::registerObject(ConfigurationTabBuilder::HOOK_NAMESPACE, 'test3', $tab3);

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
        $builder = new ConfigurationTabBuilder($widget);

        $tab = Mockery::mock('Tab');
        Hook::registerObject(ConfigurationTabBuilder::HOOK_NAMESPACE, 'misc', $tab);
        $builder->build();

        $this->assertCount(5, $builder->getTabs());
    }
}
