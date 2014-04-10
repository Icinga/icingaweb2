<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web\Widget;

use Icinga\Test\BaseTestCase;
use Tests\Icinga\Web\RequestMock;
use Tests\Icinga\Web\ViewMock;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Url;

/**
 * Test creation and rendering of tabs
 */
class TabTest extends BaseTestCase
{
    /**
     * Test whether rendering a tab without URL is done correctly
     */
    public function testRenderWithoutUrl()
    {
        $this->markTestSkipped('We cannot pass view objects to widgets anymore!1!!11!!1!!!');
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
     */
    public function testActiveTab()
    {
        $this->markTestSkipped('We cannot pass view objects to widgets anymore!1!!11!!1!!!');
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
     */
    public function testTabWithUrl()
    {
        $this->markTestSkipped('We cannot pass view objects to widgets anymore!1!!11!!1!!!');
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
     */
    public function testTabWithIconImage()
    {
        $this->markTestSkipped('We cannot pass view objects to widgets anymore!1!!11!!1!!!');
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
     */
    public function testTabWithIconClass()
    {
        $this->markTestSkipped('We cannot pass view objects to widgets anymore!1!!11!!1!!!');
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
