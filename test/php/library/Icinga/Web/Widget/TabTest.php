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
require_once('../../library/Icinga/Web/Url.php');
require_once('library/Icinga/Web/RequestMock.php');
require_once('library/Icinga/Web/ViewMock.php');
require_once('Zend/View/Abstract.php');

use Icinga\Web\View;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Url;
use Tests\Icinga\Web\RequestMock;
use \Zend_View_Abstract;
use \PHPUnit_Framework_TestCase;

use Tests\Icinga\Web\ViewMock;

/**
 * Test creation and rendering of tabs
 *
 */
class TabTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test whether rendering a tab without URL is done correctly
     *
     */
    public function testRenderWithoutUrl()
    {
        $tab = new Tab(array("name" => "tab", "title" => "Title text"));
        $html = $tab->render(new ViewMock());

        $this->assertEquals(
            1,
            preg_match(
                '/<li *> *Title text *<\/li> */i',
                $html
            ),
            "Asserting an tab without url only showing a title, , got " . $html
        );
    }

    /**
     * Test whether rendering an active tab adds the 'class' property
     *
     */
    public function testActiveTab()
    {
        $tab = new Tab(array("name" => "tab", "title" => "Title text"));
        $tab->setActive(true);

        $html = $tab->render(new ViewMock());
        $this->assertEquals(
            1,
            preg_match(
                '/<li *class="active" *> *Title text *<\/li> */i',
                $html
            ),
            "Asserting an active tab having the 'active' class provided, got " . $html
        );
    }

    /**
     * Test whether rendering a tab with URL adds a n &gt;a&lt; tag correctly
     *
     */
    public function testTabWithUrl()
    {
        $tab = new Tab(
            array(
                "name"  => "tab",
                "title" => "Title text",
                "url"   => Url::fromPath("my/url", array(), new RequestMock())
            )
        );
        $html = $tab->render(new ViewMock());
        $this->assertEquals(
            1,
            preg_match(
                '/<li *><a href="\/my\/url".*>Title text<\/a><\/li>/i',
                $html
            ),
            'Asserting an url being rendered inside an HTML anchor. got ' . $html
        );
    }

    /**
     * Test wheter the 'icon' property adds an img tag
     *
     */
    public function testTabWithIconImage()
    {
        $tab = new Tab(
            array(
                "name"  => "tab",
                "title" => "Title text",
                "icon"   => Url::fromPath("my/url", array(), new RequestMock())
            )
        );
        $html = $tab->render(new ViewMock());
        $this->assertEquals(
            1,
            preg_match(
                '/<li *><img src="\/my\/url" .*?\/> Title text<\/li>/i',
                $html
            ),
            'Asserting an url being rendered inside an HTML anchor. got ' . $html
        );
    }

    /**
     * Test wheter the iconCls property adds an i tag with the icon
     *
     */
    public function testTabWithIconClass()
    {
        $tab = new Tab(
            array(
                "name"      => "tab",
                "title"     => "Title text",
                "iconCls"   => "myIcon"
            )
        );
        $html = $tab->render(new ViewMock());
        $this->assertEquals(
            1,
            preg_match(
                '/<li *><i class="myIcon"><\/i> Title text<\/li>/i',
                $html
            ),
            'Asserting an url being rendered inside an HTML anchor. got ' . $html
        );
    }
}
