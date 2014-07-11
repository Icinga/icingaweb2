<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web;

use Zend_Config;
use Icinga\Web\Menu;
use Icinga\Test\BaseTestCase;

class MenuTest extends BaseTestCase
{
    public function testWhetherMenusAreNaturallySorted()
    {
        $menu = new Menu('test');
        $menu->addSubMenu(5, new Zend_Config(array('title' => 'ccc5')));
        $menu->addSubMenu(0, new Zend_Config(array('title' => 'aaa')));
        $menu->addSubMenu(3, new Zend_Config(array('title' => 'ccc')));
        $menu->addSubMenu(2, new Zend_Config(array('title' => 'bbb')));
        $menu->addSubMenu(4, new Zend_Config(array('title' => 'ccc2')));
        $menu->addSubMenu(1, new Zend_Config(array('title' => 'bb')));

        $this->assertEquals(
            array('aaa', 'bb', 'bbb', 'ccc', 'ccc2', 'ccc5'),
            array_map(function ($m) { return $m->getTitle(); }, iterator_to_array($menu->order())),
            'Menu::order() does not return its elements in natural order'
        );
    }
}
