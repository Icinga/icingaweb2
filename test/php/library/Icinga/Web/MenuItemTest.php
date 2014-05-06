<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web;

use Zend_Config;
use Icinga\Web\MenuItem;
use Icinga\Test\BaseTestCase;

class MenuItemTest extends BaseTestCase
{
    public function testWhetherMenuItemsAreNaturallySorted()
    {
        $item = new MenuItem('test');
        $item->addChild(5, new Zend_Config(array('title' => 'ccc5')));
        $item->addChild(0, new Zend_Config(array('title' => 'aaa')));
        $item->addChild(3, new Zend_Config(array('title' => 'ccc')));
        $item->addChild(2, new Zend_Config(array('title' => 'bbb')));
        $item->addChild(4, new Zend_Config(array('title' => 'ccc2')));
        $item->addChild(1, new Zend_Config(array('title' => 'bb')));

        $this->assertEquals(
            array('aaa', 'bb', 'bbb', 'ccc', 'ccc2', 'ccc5'),
            array_map(function ($it) { return $it->getTitle(); }, $item->getChildren()),
            'MenuItem::getChildren does not return its elements in natural order'
        );
    }
}
