<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web\Widget;

use Icinga\Test\BaseTestCase;
use Tests\Icinga\Web\ViewMock;
use Icinga\Web\Widget\Tabs;

/**
 * Test rendering of tabs and corretct tab management
 */
class TabsTest extends BaseTestCase
{
    /**
     * Test adding tabs and asserting for correct count
     */
    public function testAddTabs()
    {
        $tabs = new Tabs();
        $this->assertEquals(0, $tabs->count(), 'Asserting a tab bar starting with no items');
        $tabs->add('tab1', array('title' => 'Tab 1'));
        $tabs->add('tab2', array('title' => 'Tab 2'));
        $this->assertEquals(2, $tabs->count(), 'Asserting a tab bar containing 2 items after being added');

        $this->assertTrue(
            $tabs->has('tab1'),
            'Asserting the tab bar to determine the existence of added tabs correctly (tab1)'
        );

        $this->assertTrue(
            $tabs->has('tab2'),
            'Asserting the tab bar to determine the existence of added tabs correctly (tab2)'
        );
    }

    /**
     * Test rendering of tabs when no dropdown is requested
     */
    public function testRenderTabsWithoutDropdown()
    {
        $this->markTestSkipped('We cannot pass view objects to widgets anymore!1!!11!!1!!!');
        $tabs = new Tabs();

        $tabs->add('tab1', array('title' => 'Tab 1'));
        $tabs->add('tab2', array('title' => 'Tab 2'));

        $html = $tabs->render(new ViewMock());
        $this->assertContains('<li >Tab 1</li>', $html, 'Asserting tab 1 being rendered correctly' . $html);
        $this->assertContains('<li >Tab 2</li>', $html, 'Asserting tab 2 being rendered correctly' . $html);
        $this->assertNotContains('class="dropdown ', 'Asserting the dropdown to not be rendered' . $html);
    }

    /**
     * Test rendering of tabs when dropdown is requested
     */
    public function testRenderDropdown()
    {
        $this->markTestSkipped('We cannot pass view objects to widgets anymore!1!!11!!1!!!');
        $tabs = new Tabs();

        $tabs->add('tab1', array('title' => 'Tab 1'));
        $tabs->addAsDropdown('tab2', array('title' => 'Tab 2'));

        $html = $tabs->render(new ViewMock());
        $this->assertContains('<li >Tab 1</li>', $html, 'Asserting tab 1 being rendered correctly ' . $html);
        $this->assertContains('<li >Tab 2</li>', $html, 'Asserting tab 2 being rendered correctly ' . $html);
        $this->assertContains('class="dropdown ', 'Asserting the dropdown to be rendered, got ' . $html);
    }
}
