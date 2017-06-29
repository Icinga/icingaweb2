<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web\Paginator\ScrollingStyle;

use Mockery;
use Zend_Paginator;
use Icinga\Test\BaseTestCase;

require_once realpath(BaseTestCase::$libDir . '/Web/Paginator/ScrollingStyle/SlidingWithBorder.php');

class SlidingwithborderTest extends BaseTestCase
{
    public function testGetPages2()
    {
        $scrollingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();
        $paginator = new Zend_Paginator($this->getPaginatorAdapter());

        $pages = $scrollingStyle->getPages($paginator);
        $this->assertInternalType('array', $pages);
        $this->assertCount(10, $pages);
        $this->assertEquals('...', $pages[8]);
    }

    public function testGetPages3()
    {
        $scrollingStyle = new \Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder();
        $paginator = new Zend_Paginator($this->getPaginatorAdapter());
        $paginator->setCurrentPageNumber(9);

        $pages = $scrollingStyle->getPages($paginator);
        $this->assertInternalType('array', $pages);
        $this->assertCount(10, $pages);
        $this->assertEquals('...', $pages[3]);
        $this->assertEquals('...', $pages[12]);
    }

    protected function getPaginatorAdapter()
    {
        return Mockery::mock('\Zend_Paginator_Adapter_Interface')->shouldReceive('count')->andReturn(1000)->getMock();
    }
}
