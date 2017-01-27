<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web;

use Icinga\Web\Menu;
use Icinga\Test\BaseTestCase;
use Icinga\Data\ConfigObject;

class MenuTest extends BaseTestCase
{
    public function testWhetherMenusAreNaturallySorted()
    {
        $menu = new Menu('test');
        $menu->addSubMenu(5, new ConfigObject(array('title' => 'ccc5')));
        $menu->addSubMenu(0, new ConfigObject(array('title' => 'aaa')));
        $menu->addSubMenu(3, new ConfigObject(array('title' => 'ccc')));
        $menu->addSubMenu(2, new ConfigObject(array('title' => 'bbb')));
        $menu->addSubMenu(4, new ConfigObject(array('title' => 'ccc2')));
        $menu->addSubMenu(1, new ConfigObject(array('title' => 'bb')));

        $this->assertEquals(
            array('aaa', 'bb', 'bbb', 'ccc', 'ccc2', 'ccc5'),
            array_map(function ($m) {
                return $m->getTitle();
            }, iterator_to_array($menu->order())),
            'Menu::order() does not return its elements in natural order'
        );
    }
}
