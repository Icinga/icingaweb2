<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}


namespace Tests\Icinga\Web\Widget;

require_once('../../library/Icinga/Web/Widget/Widget.php');
require_once('../../library/Icinga/Web/Widget/Tab.php');
require_once('../../library/Icinga/Web/Widget/Tabs.php');
require_once('../../library/Icinga/Web/Url.php');

require_once('library/Icinga/Web/ViewMock.php');
require_once('Zend/View/Abstract.php');

use Icinga\Web\View;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabs;
use Tests\Icinga\Web\ViewMock;

use \Zend_View_Abstract;
use \PHPUnit_Framework_TestCase;

/**
 * Test rendering of tabs and corretct tab management
 *
 */
class TabsTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test adding tabs and asserting for correct count
     *
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
     *
     */
    public function testRenderTabsWithoutDropdown()
    {
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
     *
     */
    public function testRenderDropdown()
    {
        $tabs = new Tabs();

        $tabs->add('tab1', array('title' => 'Tab 1'));
        $tabs->addAsDropdown('tab2', array('title' => 'Tab 2'));

        $html = $tabs->render(new ViewMock());
        $this->assertContains('<li >Tab 1</li>', $html, 'Asserting tab 1 being rendered correctly ' . $html);
        $this->assertContains('<li >Tab 2</li>', $html, 'Asserting tab 2 being rendered correctly ' . $html);
        $this->assertContains('class="dropdown ', 'Asserting the dropdown to be rendered, got ' . $html);
    }
}
